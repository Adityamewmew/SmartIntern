<?php

namespace App\Http\Controllers\Admin;

use App\Constants\LogBookConst;
use App\Constants\ResponseConst;
use App\Constants\UserConst;
use App\Http\Controllers\Controller;
use App\Usecase\LogBookUsecase;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LogBookController extends Controller
{
    protected array $page = [
        'route' => 'log-book',
        'title' => 'Log Book Harian',
    ];

    protected string $baseRedirect;

    public function __construct(
        protected LogBookUsecase $usecase
    ) {
        $this->baseRedirect = 'admin/'.$this->page['route'];
    }

    public function index(Request $request): View|Response
    {
        $currentMonth = date('n'); // 1-12
        $currentYear = date('Y');

        // Default to current month and year if not specified,
        // unless they explicitly want to view all (e.g. by passing empty month/year in query param, though usually default is current month)
        // Let's set default if it's completely missing from request.
        $month = $request->has('month') ? $request->get('month') : $currentMonth;
        $year = $request->has('year') ? $request->get('year') : $currentYear;

        $filters = [
            'keywords' => $request->get('keywords'),
            'month' => $month,
            'year' => $year,
            'user_id' => $request->get('user_id'),
            'filter_status' => $request->get('filter_status'),
        ];

        $userOptions = [];
        if (Auth::user()?->access_type == UserConst::SUPERADMIN) {
            $userOptions = $this->usecase->getUsersOptions();
        }

        $isSuperadmin = Auth::user()?->access_type == UserConst::SUPERADMIN;
        $isCalendarView = empty($filters['keywords'])
            && ! empty($month)
            && ! empty($year)
            && (! $isSuperadmin || ! empty($user_id));

        if ($isCalendarView) {
            $response = $this->usecase->getCalendarData($filters);
        } else {
            $response = $this->usecase->getAll($filters);
        }
        $data = $response['data']['list'] ?? [];

        return view('_admin.log-book.index', [
            'data' => $data,
            'page' => $this->page,
            'keywords' => $request->get('keywords'),
            'month' => $month,
            'year' => $year,
            'user_id' => $request->get('user_id'),
            'isCalendarView' => $isCalendarView,
            'monthOptions' => LogBookConst::getMonthOptions(),
            'yearOptions' => $this->usecase->getYearOptions(),
            'userOptions' => $userOptions,
        ]);
    }

    public function add(Request $request): View|Response
    {
        return view('_admin.log-book.add', [
            'page' => $this->page,
            'defaultLogDate' => $request->get('log_date'),
        ]);
    }

    public function doCreate(Request $request): RedirectResponse
    {
        $process = $this->usecase->create(
            data: $request,
        );

        if ($process['success']) {
            return redirect()
                ->route('admin.log_book.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_CREATED);
        } else {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }

    public function detail(int $id): View|RedirectResponse|Response
    {
        $data = $this->usecase->getByID($id);

        if (empty($data['data'])) {
            return redirect()
                ->intended($this->baseRedirect)
                ->with('error', ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
        $images = $data['data']['images'] ?? [];
        unset($data['data']['images']);

        return view('_admin.log-book.detail', [
            'data' => (object) $data['data'],
            'images' => $images,
            'page' => $this->page,
        ]);
    }

    public function update(int $id): View|RedirectResponse|Response
    {
        $data = $this->usecase->getByID($id);

        if (empty($data['data'])) {
            return redirect()
                ->intended($this->baseRedirect)
                ->with('error', ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
        $images = $data['data']['images'] ?? [];
        unset($data['data']['images']);

        return view('_admin.log-book.update', [
            'data' => (object) $data['data'],
            'images' => $images,
            'logId' => $id,
            'page' => $this->page,
        ]);
    }

    public function doUpdate(int $id, Request $request): RedirectResponse
    {
        $process = $this->usecase->update(
            data: $request,
            id: $id,
        );

        if ($process['success']) {
            return redirect()
                ->route('admin.log_book.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_UPDATED);
        } else {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }

    public function delete(int $id): RedirectResponse
    {
        $process = $this->usecase->delete(id: $id);

        if ($process['success']) {
            return redirect()
                ->route('admin.log_book.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_DELETED);
        } else {
            return redirect()
                ->route('admin.log_book.index')
                ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }

    public function approve(int $id): RedirectResponse
    {
        $process = $this->usecase->approve(id: $id);

        if ($process['success']) {
            return redirect()
                ->back()
                ->with('success', $process['message']);
        } else {
            return redirect()
                ->back()
                ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }

    public function deleteImage(int $imageId): RedirectResponse
    {
        $process = $this->usecase->deleteImage(id: $imageId);

        if ($process['success']) {
            return redirect()
                ->back()
                ->with('success', ResponseConst::SUCCESS_MESSAGE_DELETED);
        } else {
            return redirect()
                ->back()
                ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
        }
    }

    public function exportExcel(Request $request)
    {
        $filters = [
            'keywords' => $request->get('keywords'),
            'month' => $request->get('month'),
            'year' => $request->get('year'),
            'user_id' => $request->get('user_id'),
        ];

        $process = $this->usecase->getExportData($filters);
        $data = $process['success'] ? $process['data']['list'] : [];

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Tanggal');
        $sheet->setCellValue('B1', 'Pembuat');
        $sheet->setCellValue('C1', 'Kehadiran');
        $sheet->setCellValue('D1', 'Judul');
        $sheet->setCellValue('E1', 'Deskripsi');

        $row = 2;
        foreach ($data as $item) {
            $isHoliday = $item->is_holiday ?? false;
            $isWeekend = $item->is_weekend ?? false;
            $isEmpty = $item->is_empty ?? false;

            $sheet->setCellValue('A'.$row, $item->log_date ? Carbon::parse($item->log_date)->translatedFormat('d M Y') : '-');
            $sheet->setCellValue('B'.$row, $item->user_name ?? '-');

            if ($isEmpty) {
                $sheet->setCellValue('C'.$row, '-');
                if ($isHoliday) {
                    $sheet->setCellValue('D'.$row, 'Libur Nasional: '.$item->holiday_name);
                } elseif ($isWeekend) {
                    $sheet->setCellValue('D'.$row, 'Libur Akhir Pekan');
                } else {
                    $sheet->setCellValue('D'.$row, 'Belum Diisi');
                }
                $sheet->setCellValue('E'.$row, '-');
            } else {
                $attendanceStr = '-';
                if (isset($item->attendance_status)) {
                    if ($item->attendance_status === 'masuk') $attendanceStr = 'Masuk';
                    elseif ($item->attendance_status === 'izin') $attendanceStr = 'Izin';
                    elseif ($item->attendance_status === 'izin_sakit') $attendanceStr = 'Izin Sakit';
                }
                $sheet->setCellValue('C'.$row, $attendanceStr);
                $sheet->setCellValue('D'.$row, $item->title);
                $sheet->setCellValue('E'.$row, $item->description);
            }

            // Apply red color for weekends/holidays
            if ($isHoliday || $isWeekend) {
                $sheet->getStyle('A'.$row.':E'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEE2E2');
                if ($isHoliday) {
                    $sheet->getStyle('D'.$row)->getFont()->getColor()->setARGB('FFEF4444');
                    $sheet->getStyle('D'.$row)->getFont()->setBold(true);
                } elseif ($isWeekend) {
                    $sheet->getStyle('D'.$row)->getFont()->getColor()->setARGB('FFEF4444');
                    $sheet->getStyle('D'.$row)->getFont()->setBold(true);
                }
            }

            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'logbook-'.date('Ymd-His').'.xlsx';

        // Output to buffer and return as response
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content)
            ->header('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function exportPdf(Request $request)
    {
        $filters = [
            'keywords' => $request->get('keywords'),
            'month' => $request->get('month'),
            'year' => $request->get('year'),
            'user_id' => $request->get('user_id'),
        ];

        $process = $this->usecase->getExportData($filters);
        $data = $process['success'] ? $process['data']['list'] : [];

        $pdf = Pdf::loadView('_admin.log-book.pdf', ['data' => $data]);

        return $pdf->download('logbook-'.date('Ymd-His').'.pdf');
    }
}
