<?php

namespace App\Http\Controllers\Admin;

use App\Constants\LogBookConst;
use App\Constants\ResponseConst;
use App\Http\Controllers\Controller;
use App\Usecase\LogBookUsecase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        $filters = [
            'keywords' => $request->get('keywords'),
            'month' => $request->get('month'),
            'year' => $request->get('year'),
        ];

        $data = $this->usecase->getAll($filters);
        $data = $data['data']['list'] ?? [];

        return view('_admin.log-book.index', [
            'data' => $data,
            'page' => $this->page,
            'keywords' => $request->get('keywords'),
            'month' => $request->get('month'),
            'year' => $request->get('year'),
            'monthOptions' => LogBookConst::getMonthOptions(),
            'yearOptions' => $this->usecase->getYearOptions(),
        ]);
    }

    public function add(): View|Response
    {
        return view('_admin.log-book.add', [
            'page' => $this->page,
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
}
