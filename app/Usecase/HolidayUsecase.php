<?php

namespace App\Usecase;

use App\Constants\DatabaseConst;
use App\Constants\ResponseConst;
use App\Http\Presenter\Response;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class HolidayUsecase
{
    public string $className = 'HolidayUsecase';

    public function getAll(array $filterData = []): array
    {
        try {
            $query = DB::table(DatabaseConst::HOLIDAY().' as h')
                ->select('h.*')
                ->whereNull('h.deleted_at')
                ->when(! empty($filterData['keywords']), function ($query) use ($filterData) {
                    return $query->where('h.holiday_name', 'like', '%'.$filterData['keywords'].'%');
                })
                ->when(! empty($filterData['year']), function ($query) use ($filterData) {
                    return $query->whereYear('h.holiday_date', $filterData['year']);
                })
                ->when(! empty($filterData['month']), function ($query) use ($filterData) {
                    return $query->whereMonth('h.holiday_date', $filterData['month']);
                })
                ->orderBy('h.holiday_date', 'desc');

            if (! empty($filterData['no_pagination'])) {
                $data = $query->get();
            } else {
                $data = $query->paginate(20);
                if (! empty($filterData)) {
                    $data->appends($filterData);
                }
            }

            return Response::buildSuccess(['list' => $data], ResponseConst::HTTP_SUCCESS);
        } catch (Exception $e) {
            Log::error(message: $e->getMessage(), context: ['method' => __METHOD__]);

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function getByID(int $id): array
    {
        try {
            $data = DB::table(DatabaseConst::HOLIDAY())
                ->whereNull('deleted_at')
                ->where('id', $id)
                ->first();

            return Response::buildSuccess(data: collect($data)->toArray());
        } catch (Exception $e) {
            Log::error(message: $e->getMessage(), context: ['method' => __METHOD__]);

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function create(Request $data): array
    {
        $validator = Validator::make($data->all(), [
            'holiday_date' => 'required|date',
            'holiday_name' => 'required|string|max:255',
        ]);

        $validator->validate();

        DB::beginTransaction();
        try {
            DB::table(DatabaseConst::HOLIDAY())->insert([
                'holiday_date' => $data['holiday_date'],
                'holiday_name' => $data['holiday_name'],
                'created_by' => Auth::user()?->id,
                'created_at' => now(),
            ]);

            DB::commit();

            return Response::buildSuccessCreated();
        } catch (Exception $e) {
            DB::rollback();
            Log::error(message: $e->getMessage(), context: ['method' => __METHOD__]);

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function update(Request $data, int $id): array
    {
        $validator = Validator::make($data->all(), [
            'holiday_date' => 'required|date',
            'holiday_name' => 'required|string|max:255',
        ]);
        $validator->validate();

        DB::beginTransaction();
        try {
            $payload = $data->only(['holiday_date', 'holiday_name']);
            $payload['updated_by'] = Auth::user()?->id;
            $payload['updated_at'] = now();

            DB::table(DatabaseConst::HOLIDAY())->where('id', $id)->update($payload);
            DB::commit();

            return Response::buildSuccess(message: ResponseConst::SUCCESS_MESSAGE_UPDATED);
        } catch (Exception $e) {
            DB::rollback();
            Log::error(message: $e->getMessage(), context: ['method' => __METHOD__]);

            return Response::buildErrorService($e->getMessage());
        }
    }

    public function delete(int $id): array
    {
        DB::beginTransaction();
        try {
            $delete = DB::table(DatabaseConst::HOLIDAY())->where('id', $id)->update([
                'deleted_by' => Auth::user()?->id,
                'deleted_at' => now(),
            ]);

            if (! $delete) {
                DB::rollback();
                throw new Exception('FAILED DELETE DATA');
            }

            DB::commit();

            return Response::buildSuccess(message: ResponseConst::SUCCESS_MESSAGE_DELETED);
        } catch (Exception $e) {
            DB::rollback();
            Log::error(message: $e->getMessage(), context: ['method' => __METHOD__]);

            return Response::buildErrorService($e->getMessage());
        }
    }
}
