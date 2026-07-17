<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Constants\ResponseConst;
use App\Constants\UserConst;
use App\Http\Presenter\Response;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use stdClass;

class LogBookUsecase extends Usecase
{
    /**
     * Get all daily logs with pagination and filters.
     *
     * @param  array{keywords?: string, month?: string, year?: string, no_pagination?: bool}  $filterData
     */
    public function getAll(array $filterData = []): array
    {
        try {
            $query = DB::table(DatabaseConst::DAILY_LOG().' as dl')
                ->leftJoin(DatabaseConst::USER().' as u', 'dl.user_id', '=', 'u.id')
                ->select('dl.*', 'u.name as user_name')
                ->whereNull('dl.deleted_at')
                ->when($filterData['keywords'] ?? false, function ($query, $keywords) {
                    return $query->where(function ($q) use ($keywords) {
                        $q->where('dl.title', 'like', '%'.$keywords.'%')
                            ->orWhere('dl.description', 'like', '%'.$keywords.'%');
                    });
                })
                ->when($filterData['month'] ?? false, function ($query, $month) {
                    return $query->whereMonth('dl.log_date', $month);
                })
                ->when($filterData['year'] ?? false, function ($query, $year) {
                    return $query->whereYear('dl.log_date', $year);
                });

            if (Auth::user()?->access_type != UserConst::SUPERADMIN) {
                $query->where('dl.user_id', Auth::user()?->id);
            } else {
                if (! empty($filterData['user_id'])) {
                    $query->where('dl.user_id', $filterData['user_id']);
                }
            }

            $query->orderBy('dl.log_date', 'desc')
                ->orderBy('dl.created_at', 'desc');

            $data = empty($filterData['no_pagination'])
                ? $query->paginate(20)
                : $query->get();

            if (! empty($filterData) && method_exists($data, 'appends')) {
                $data->appends($filterData);
            }

            return Response::buildSuccess(
                [
                    'list' => $data,
                ],
                ResponseConst::HTTP_SUCCESS
            );
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Calendar view: every day of the target month, real logs merged with
     * dummy rows for empty days. Holidays come from external API (cached 30d).
     *
     * @param  array{month?: string, year?: string, user_id?: string}  $filterData
     */
    public function getCalendarData(array $filterData = []): array
    {
        try {
            $month = (int) ($filterData['month'] ?? date('n'));
            $year = (int) ($filterData['year'] ?? date('Y'));
            $isSuperadmin = Auth::user()?->access_type == UserConst::SUPERADMIN;
            $targetUserId = $isSuperadmin ? ($filterData['user_id'] ?? null) : Auth::user()?->id;

            $query = DB::table(DatabaseConst::DAILY_LOG().' as dl')
                ->leftJoin(DatabaseConst::USER().' as u', 'dl.user_id', '=', 'u.id')
                ->select('dl.*', 'u.name as user_name')
                ->whereNull('dl.deleted_at')
                ->whereMonth('dl.log_date', $month)
                ->whereYear('dl.log_date', $year)
                ->when($targetUserId, function ($query, $userId) {
                    return $query->where('dl.user_id', $userId);
                })
                ->orderBy('dl.log_date', 'asc');

            $byDate = [];
            $maxLogDate = null;
            // ponytail: cursor() streams rows 1-at-a-time, month is bounded so memory stays flat
            foreach ($query->cursor() as $log) {
                $dateKey = Carbon::parse($log->log_date)->format('Y-m-d');
                $byDate[$dateKey] = $log;
                if ($maxLogDate === null || $dateKey > $maxLogDate) {
                    $maxLogDate = $dateKey;
                }
            }

            $holidays = $this->getHolidays($year, $month);

            $today = Carbon::now()->format('Y-m-d');

            $day = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = $day->copy()->endOfMonth();

            // --- LOGIKA 1: untuk testing input masa depan
            // $maxAllowedDate = max(array_filter([$today, $maxLogDate])) ?: $today;

            // --- LOGIKA 2: Real implementation
            if ($day->greaterThan(Carbon::today())) {
                return Response::buildSuccess(['list' => []], ResponseConst::HTTP_SUCCESS);
            }
            $maxAllowedDate = $day->isSameMonth(Carbon::today())
                ? $today
                : $end->format('Y-m-d');

            $rows = [];

            foreach (CarbonPeriod::create($day, $end) as $currentDate) {
                $dateKey = $currentDate->format('Y-m-d');
                $holidayName = $holidays[$dateKey] ?? null;
                $isWeekend = $currentDate->isWeekend();
                $isHoliday = $holidayName !== null;

                if (isset($byDate[$dateKey])) {
                    $row = $byDate[$dateKey];
                    $row->is_empty = false;
                    $row->is_weekend = $isWeekend;
                    $row->is_holiday = $isHoliday;
                    $row->holiday_name = $holidayName;
                    $rows[] = $row;

                    continue;
                }

                // Skip empty days beyond the time horizon so future days don't fill with "Belum Diisi".
                if ($dateKey > $maxAllowedDate) {
                    continue;
                }

                $dummy = new stdClass;
                $dummy->id = null;
                $dummy->log_date = $dateKey;
                $dummy->title = null;
                $dummy->description = null;
                $dummy->user_name = null;
                $dummy->is_empty = true;
                $dummy->is_weekend = $isWeekend;
                $dummy->is_holiday = $isHoliday;
                $dummy->holiday_name = $holidayName;
                $rows[] = $dummy;
            }

            if (isset($filterData['filter_status'])) {
                $statusFilter = $filterData['filter_status'];
                $rows = array_filter($rows, function ($row) use ($statusFilter) {
                    if ($statusFilter === 'empty') {
                        return $row->is_empty === true && $row->is_holiday === false && $row->is_weekend === false;
                    }
                    if ($statusFilter === 'holiday') {
                        return $row->is_holiday === true || $row->is_weekend === true;
                    }

                    return true;
                });
                $rows = array_values($rows);
            }

            $rows = array_reverse($rows);

            return Response::buildSuccess(['list' => $rows], ResponseConst::HTTP_SUCCESS);
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Fetch national holidays for a month, cached 30 days.
     * Returns date(Y-m-d) => holiday name. Empty array on any failure.
     *
     * @return array<string, string>
     */
    protected function getHolidays(int $year, int $month): array
    {
        try {
            $holidays = DB::table(DatabaseConst::HOLIDAY())
                ->whereNull('deleted_at')
                ->whereYear('holiday_date', $year)
                ->whereMonth('holiday_date', $month)
                ->get();

            $map = [];
            foreach ($holidays as $item) {
                $dateKey = Carbon::parse($item->holiday_date)->format('Y-m-d');
                $map[$dateKey] = $item->holiday_name;
            }

            return $map;
        } catch (Exception $e) {
            Log::warning(message: 'Holiday DB query failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get data for export including images without pagination
     */
    public function getExportData(array $filterData = []): array
    {
        try {
            $logsResponse = $this->getCalendarData($filterData);

            if (! $logsResponse['success']) {
                return $logsResponse;
            }

            $logs = $logsResponse['data']['list'];
            $logIds = collect($logs)->pluck('id')->filter()->toArray();

            $images = collect([]);
            if (count($logIds) > 0) {
                $images = DB::table(DatabaseConst::DAILY_LOG_IMAGE())
                    ->whereIn('daily_log_id', $logIds)
                    ->orderBy('sort_order')
                    ->get()
                    ->groupBy('daily_log_id');
            }

            foreach ($logs as $log) {
                if (! empty($log->id)) {
                    $log->images = $images->get($log->id) ?? collect([]);
                } else {
                    $log->images = collect([]);
                }
            }

            return Response::buildSuccess(['list' => $logs]);
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Get year options for filter: from oldest log year to current year.
     *
     * @return array<int, int>
     */
    public function getYearOptions(): array
    {
        $currentYear = (int) Carbon::now()->format('Y');

        $minYear = (int) DB::table(DatabaseConst::DAILY_LOG())
            ->selectRaw('MIN(YEAR(log_date)) as min_year')
            ->value('min_year');

        if ($minYear < 1) {
            $minYear = $currentYear;
        }

        $years = [];

        for ($y = $currentYear; $y >= $minYear; $y--) {
            $years[$y] = $y;
        }

        return $years;
    }

    public function getByID(int $id): array
    {
        try {
            $data = DB::table(DatabaseConst::DAILY_LOG().' as dl')
                ->leftJoin(DatabaseConst::USER().' as u', 'dl.user_id', '=', 'u.id')
                ->select('dl.*', 'u.name as user_name')
                ->whereNull('dl.deleted_at')
                ->where('dl.id', $id)
                ->first();

            if (! $data) {
                return Response::buildErrorNotFound(ResponseConst::DEFAULT_ERROR_MESSAGE);
            }

            if (Auth::user()?->access_type != UserConst::SUPERADMIN && $data->user_id != Auth::user()?->id) {
                return Response::buildErrorNotFound('Anda tidak memiliki akses untuk melihat data ini.');
            }

            $images = DB::table(DatabaseConst::DAILY_LOG_IMAGE())
                ->where('daily_log_id', $id)
                ->orderBy('sort_order')
                ->get();

            return Response::buildSuccess(
                data: [
                    ...collect($data)->toArray(),
                    'images' => $images,
                ]
            );
        } catch (Exception $e) {
            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function create(Request $data): array
    {
        $validator = Validator::make($data->all(), [
            'log_date' => 'required|date',
            'attendance_status' => 'required|in:masuk,izin,izin_sakit',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $validator->validate();

        DB::beginTransaction();
        try {
            $logId = DB::table(DatabaseConst::DAILY_LOG())
                ->insertGetId([
                    'user_id' => Auth::user()?->id,
                    'log_date' => $data['log_date'],
                    'attendance_status' => $data['attendance_status'],
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'created_by' => Auth::user()?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->storeImages($data, $logId);

            DB::commit();

            return Response::buildSuccessCreated();
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function update(Request $data, int $id): array
    {
        $validator = Validator::make($data->all(), [
            'log_date' => 'required|date',
            'attendance_status' => 'required|in:masuk,izin,izin_sakit',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $validator->validate();

        DB::beginTransaction();
        try {
            $log = DB::table(DatabaseConst::DAILY_LOG())->where('id', $id)->whereNull('deleted_at')->first();
            if (! $log) {
                return Response::buildErrorNotFound(ResponseConst::DEFAULT_ERROR_MESSAGE);
            }
            if ($log->user_id != Auth::user()?->id) {
                return Response::buildErrorService('Anda tidak memiliki akses untuk mengubah data ini.');
            }

            DB::table(DatabaseConst::DAILY_LOG())
                ->where('id', $id)
                ->update([
                    'log_date' => $data['log_date'],
                    'attendance_status' => $data['attendance_status'],
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'updated_by' => Auth::user()?->id,
                    'updated_at' => now(),
                ]);

            $this->storeImages($data, $id);

            DB::commit();

            return Response::buildSuccess(
                message: ResponseConst::SUCCESS_MESSAGE_UPDATED
            );
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function approve(int $id): array
    {
        DB::beginTransaction();
        try {
            $log = DB::table(DatabaseConst::DAILY_LOG())->where('id', $id)->whereNull('deleted_at')->first();
            if (! $log) {
                return Response::buildErrorNotFound(ResponseConst::DEFAULT_ERROR_MESSAGE);
            }
            if (Auth::user()?->access_type != UserConst::SUPERADMIN) {
                return Response::buildErrorService('Anda tidak memiliki akses untuk menyetujui logbook.');
            }

            DB::table(DatabaseConst::DAILY_LOG())
                ->where('id', $id)
                ->update([
                    'status' => 'sudah_direview',
                    'updated_by' => Auth::user()?->id,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return Response::buildSuccess(
                message: 'Logbook berhasil disetujui.'
            );
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        DB::beginTransaction();
        try {
            $log = DB::table(DatabaseConst::DAILY_LOG())->where('id', $id)->whereNull('deleted_at')->first();
            if (! $log) {
                return Response::buildErrorNotFound(ResponseConst::DEFAULT_ERROR_MESSAGE);
            }
            if ($log->user_id != Auth::user()?->id) {
                return Response::buildErrorService('Anda tidak memiliki akses untuk menghapus data ini.');
            }

            $delete = DB::table(DatabaseConst::DAILY_LOG())
                ->where('id', $id)
                ->update([
                    'deleted_by' => Auth::user()?->id,
                    'deleted_at' => now(),
                ]);

            if (! $delete) {
                DB::rollback();
                throw new Exception('FAILED DELETE DATA');
            }

            DB::commit();

            return Response::buildSuccess(
                message: ResponseConst::SUCCESS_MESSAGE_DELETED
            );
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    /**
     * Store uploaded images for a daily log.
     */
    protected function storeImages(Request $data, int $logId): void
    {
        if (! $data->hasFile('images')) {
            return;
        }

        $nextSort = (int) DB::table(DatabaseConst::DAILY_LOG_IMAGE())
            ->where('daily_log_id', $logId)
            ->max('sort_order') ?? 0;

        $manager = new ImageManager(new Driver);

        foreach ($data->file('images') as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $nextSort++;
            $filename = $logId.'/'.Str::random(20).'.jpg';
            $path = 'daily-logs/'.$filename;

            // Compress and resize image
            $image = $manager->decodePath($file->getPathname());
            $image->scale(width: 700);

            $encoded = $image->encode(new JpegEncoder(quality: 80));
            Storage::disk('public')->put($path, (string) $encoded);

            DB::table(DatabaseConst::DAILY_LOG_IMAGE())->insert([
                'daily_log_id' => $logId,
                'path' => $path,
                'original_name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME).'.jpg',
                'mime' => 'image/jpeg',
                'size' => strlen((string) $encoded),
                'sort_order' => $nextSort,
                'created_by' => Auth::user()?->id,
                'created_at' => now(),
            ]);
        }
    }

    public function deleteImage(int $id): array
    {
        DB::beginTransaction();
        try {
            $image = DB::table(DatabaseConst::DAILY_LOG_IMAGE())
                ->where('id', $id)
                ->first();

            if (! $image) {
                DB::rollback();

                return Response::buildErrorNotFound(ResponseConst::DEFAULT_ERROR_MESSAGE);
            }

            $log = DB::table(DatabaseConst::DAILY_LOG())->where('id', $image->daily_log_id)->first();
            if ($log && $log->user_id != Auth::user()?->id) {
                DB::rollback();

                return Response::buildErrorService('Anda tidak memiliki akses untuk menghapus data ini.');
            }

            DB::table(DatabaseConst::DAILY_LOG_IMAGE())
                ->where('id', $id)
                ->delete();

            if ($image->path) {
                Storage::disk('public')->delete($image->path);
            }

            DB::commit();

            return Response::buildSuccess(
                message: ResponseConst::SUCCESS_MESSAGE_DELETED
            );
        } catch (Exception $e) {
            DB::rollback();

            Log::error(
                message: $e->getMessage(),
                context: [
                    'method' => __METHOD__,
                ]
            );

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function getUsersOptions(): array
    {
        try {
            $users = DB::table(DatabaseConst::USER())
                ->whereNull('deleted_at')
                ->select('id', 'name')
                ->orderBy('name', 'asc')
                ->get();

            $options = [];
            foreach ($users as $u) {
                $options[$u->id] = $u->name;
            }

            return $options;
        } catch (Exception $e) {
            return [];
        }
    }
}
