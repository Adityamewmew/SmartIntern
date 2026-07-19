<?php

namespace App\Http\Controllers\Admin;

use App\Constants\ResponseConst;
use App\Http\Controllers\Controller;
use App\Usecase\HolidayUsecase;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HolidayController extends Controller
{
    protected array $page = [
        'route' => 'holidays',
        'title' => 'Hari Libur Nasional',
    ];

    protected string $baseRedirect;

    public function __construct(
        protected HolidayUsecase $usecase
    ) {
        $this->baseRedirect = 'admin/'.$this->page['route'];
    }

    public function index(Request $request): View|Response
    {
        $year = $request->get('year');
        $month = $request->get('month');

        $data = $this->usecase->getAll([
            'keywords' => $request->get('keywords'),
            'year' => $year,
            'month' => $month,
        ]);
        $data = $data['data']['list'] ?? [];

        $years = [];
        for ($i = date('Y') - 5; $i <= date('Y') + 5; $i++) {
            $years[$i] = $i;
        }

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = Carbon::create()->month($i)->translatedFormat('F');
        }

        return view('_admin.holidays.index', [
            'data' => $data,
            'page' => $this->page,
            'keywords' => $request->get('keywords'),
            'yearOptions' => $years,
            'monthOptions' => $months,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function add(): View
    {
        return view('_admin.holidays.add', [
            'page' => $this->page,
        ]);
    }

    public function doCreate(Request $request): RedirectResponse
    {
        $process = $this->usecase->create(data: $request);

        if ($process['success']) {
            return redirect()->route('admin.holidays.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_CREATED);
        }

        return redirect()->back()->withInput()
            ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
    }

    public function update(int $id): View|RedirectResponse|Response
    {
        $data = $this->usecase->getByID($id);

        if (empty($data['data'])) {
            return redirect()->intended($this->baseRedirect)
                ->with('error', ResponseConst::DEFAULT_ERROR_MESSAGE);
        }

        return view('_admin.holidays.update', [
            'data' => (object) $data['data'],
            'page' => $this->page,
        ]);
    }

    public function doUpdate(Request $request, int $id): RedirectResponse
    {
        $process = $this->usecase->update(data: $request, id: $id);

        if ($process['success']) {
            return redirect()->route('admin.holidays.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_UPDATED);
        }

        return redirect()->back()->withInput()
            ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
    }

    public function delete(int $id): RedirectResponse
    {
        $process = $this->usecase->delete(id: $id);

        if ($process['success']) {
            return redirect()->route('admin.holidays.index')
                ->with('success', ResponseConst::SUCCESS_MESSAGE_DELETED);
        }

        return redirect()->back()
            ->with('error', $process['message'] ?? ResponseConst::DEFAULT_ERROR_MESSAGE);
    }
}
