<?php

declare(strict_types=1);

namespace Modules\Teachers\Controllers;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use Modules\Teachers\Services\TeacherService;
use RuntimeException;

final class TeacherController extends BaseController
{
    public function __construct(private readonly TeacherService $service) {}

    public function index(Request $request): Response
    {
        return $this->view('teachers.index', ['teachers' => $this->service->list()]);
    }

    public function create(Request $request): Response
    {
        return $this->view('teachers.create');
    }

    public function store(Request $request): Response
    {
        try {
            $this->service->create($request->all());
            return $this->redirect('/teachers');
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 422);
        }
    }

    public function show(Request $request): Response
    {
        try {
            return $this->view('teachers.show', ['teacher' => $this->service->find((int)$request->input('id', 0))]);
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    public function edit(Request $request): Response
    {
        try {
            return $this->view('teachers.edit', ['teacher' => $this->service->find((int)$request->input('id', 0))]);
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    public function update(Request $request): Response
    {
        try {
            $this->service->update((int)$request->input('id', 0), $request->all());
            return $this->redirect('/teachers');
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 422);
        }
    }

    public function changeStatus(Request $request): Response
    {
        try {
            $this->service->changeStatus((int)$request->input('id', 0), (string)$request->input('status', 'active'));
            return $this->redirect('/teachers');
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 422);
        }
    }

    public function subjects(Request $request): Response
    {
        try {
            $id = (int)$request->input('id', 0);
            return $this->view('teachers.subjects', [
                'teacher' => $this->service->find($id),
                'subjects' => $this->service->subjects($id),
            ]);
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    public function updateSubjects(Request $request): Response
    {
        try {
            $this->service->updateSubjects((int)$request->input('id', 0), (array)$request->input('subject_ids', []));
            return $this->redirect('/teachers/'.$request->input('id').'/subjects');
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 422);
        }
    }

    public function classes(Request $request): Response
    {
        try {
            $id = (int)$request->input('id', 0);
            return $this->view('teachers.classes', [
                'teacher' => $this->service->find($id),
                'classes' => $this->service->classes($id),
            ]);
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 404);
        }
    }

    public function updateClasses(Request $request): Response
    {
        try {
            $this->service->updateClasses((int)$request->input('id', 0), (array)$request->input('class_ids', []));
            return $this->redirect('/teachers/'.$request->input('id').'/classes');
        } catch (RuntimeException $e) {
            return Response::html($e->getMessage(), 422);
        }
    }
}
