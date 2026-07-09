<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Constants\ResponseConst;
use App\Http\Presenter\Response;
use Carbon\Carbon;
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
            $query = DB::table(DatabaseConst::DAILY_LOG())
                ->whereNull('deleted_at')
                ->when($filterData['keywords'] ?? false, function ($query, $keywords) {
                    return $query->where(function ($q) use ($keywords) {
                        $q->where('title', 'like', '%'.$keywords.'%')
                            ->orWhere('description', 'like', '%'.$keywords.'%');
                    });
                })
                ->when($filterData['month'] ?? false, function ($query, $month) {
                    return $query->whereMonth('log_date', $month);
                })
                ->when($filterData['year'] ?? false, function ($query, $year) {
                    return $query->whereYear('log_date', $year);
                })
                ->orderBy('log_date', 'desc')
                ->orderBy('created_at', 'desc');

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
     * Get data for export including images without pagination
     */
    public function getExportData(array $filterData = []): array
    {
        try {
            $filterData['no_pagination'] = true;
            $logsResponse = $this->getAll($filterData);

            if (! $logsResponse['success']) {
                return $logsResponse;
            }

            $logs = $logsResponse['data']['list'];
            $logIds = collect($logs)->pluck('id')->toArray();

            $images = DB::table(DatabaseConst::DAILY_LOG_IMAGE())
                ->whereIn('daily_log_id', $logIds)
                ->orderBy('sort_order')
                ->get()
                ->groupBy('daily_log_id');

            foreach ($logs as $log) {
                $log->images = $images->get($log->id) ?? collect([]);
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
            $data = DB::table(DatabaseConst::DAILY_LOG())
                ->whereNull('deleted_at')
                ->where('id', $id)
                ->first();

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
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $validator->validate();

        DB::beginTransaction();
        try {
            DB::table(DatabaseConst::DAILY_LOG())
                ->where('id', $id)
                ->update([
                    'log_date' => $data['log_date'],
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

    public function delete(int $id): array
    {
        DB::beginTransaction();
        try {
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
}
