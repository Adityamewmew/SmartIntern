<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Constants\LogBookConst;
use App\Constants\ResponseConst;
use App\Http\Presenter\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LogBookUsecase extends Usecase
{
    public function __construct() {}

    /**
     * Get all daily logs with pagination and filters.
     *
     * @param  array{keywords?: string, log_date_from?: string, log_date_to?: string, status?: string, no_pagination?: bool}  $filterData
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
                ->when($filterData['status'] ?? false, function ($query, $status) {
                    if ($status !== 'all') {
                        return $query->where('status', $status);
                    }
                })
                ->when($filterData['log_date_from'] ?? false, function ($query, $dateFrom) {
                    return $query->where('log_date', '>=', $dateFrom);
                })
                ->when($filterData['log_date_to'] ?? false, function ($query, $dateTo) {
                    return $query->where('log_date', '<=', $dateTo);
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

    public function getByID(int $id): array
    {
        try {
            $data = DB::table(DatabaseConst::DAILY_LOG())
                ->whereNull('deleted_at')
                ->where('id', $id)
                ->first();

            return Response::buildSuccess(
                data: collect($data)->toArray()
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
            'status' => 'required|in:'.implode(',', array_keys(LogBookConst::getStatusOptions())),
        ]);

        $validator->validate();

        DB::beginTransaction();
        try {
            DB::table(DatabaseConst::DAILY_LOG())
                ->insert([
                    'user_id' => Auth::user()?->id,
                    'log_date' => $data['log_date'],
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'status' => $data['status'],
                    'created_by' => Auth::user()?->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

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
            'status' => 'required|in:'.implode(',', array_keys(LogBookConst::getStatusOptions())),
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
                    'status' => $data['status'],
                    'updated_by' => Auth::user()?->id,
                    'updated_at' => now(),
                ]);

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
}
