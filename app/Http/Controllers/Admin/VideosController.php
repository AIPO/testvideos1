<?php

namespace App\Http\Controllers\Admin;

use App\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVideosRequest;
use App\Http\Requests\Admin\UpdateVideosRequest;
use App\Http\Controllers\Traits\FileUploadTrait;
use Yajra\DataTables\DataTables;

class VideosController extends Controller
{
    use FileUploadTrait;

    /**
     * Display a listing of Video.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!Gate::allows('video_access')) {
            return abort(401);
        }


        if (request()->ajax()) {
            $query = Video::query();
            $template = 'actionsTemplate';
            if (request('show_deleted') == 1) {

                if (!Gate::allows('video_delete')) {
                    return abort(401);
                }
                $query->onlyTrashed();
                $template = 'restoreTemplate';
            }
            $table = Datatables::of($query);

            $table->setRowAttr([
                'data-entry-id' => '{{$id}}',
            ]);
            $table->addColumn('massDelete', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');
            $table->editColumn('actions', function ($row) use ($template) {
                $gateKey = 'video_';
                $routeKey = 'admin.videos';

                return view($template, compact('row', 'gateKey', 'routeKey'));
            });
            $table->editColumn('name', function ($row) {
                return $row->name ? $row->name : '';
            });
            $table->editColumn('video', function ($row) {
                $build = '';
                foreach ($row->getMedia('video') as $media) {
                    $build .= '<p class="form-group"><a href="' . $media->getUrl() . '" target="_blank">' . $media->name . '</a></p>';
                }

                return $build;
            });

            $table->rawColumns(['actions', 'video']);

            return $table->make(true);
        }

        return view('admin.videos.index');
    }

    /**
     * Show the form for creating new Video.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!Gate::allows('video_create')) {
            return abort(401);
        }
        return view('admin.videos.create');
    }

    /**
     * Store a newly created Video in storage.
     *
     * @param  \App\Http\Requests\StoreVideosRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Gate::allows('video_create')) {
            return abort(401);
        }
        if(Request::hasFile('file')){
            $file= Request::file('file');
            $filename= $file->getClientOriginalName();
            $path = public_path().'/uploads/';
            $file->move($path,$filename);
        }
//        $request = $this->saveFiles($request);
//        $video = Video::create($request->all());
//
//
//        foreach ($request->input('video_id', []) as $index => $id) {
//            $model = config('laravel-medialibrary.media_model');
//            $file = $model::find($id);
//            $file->model_id = $video->id;
//            //  console.log ($file);
//            $file->save();
//        }

        return redirect()->route('admin.videos.index');
    }


    /**
     * Show the form for editing Video.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!Gate::allows('video_edit')) {
            return abort(401);
        }
        $video = Video::findOrFail($id);

        return view('admin.videos.edit', compact('video'));
    }

    /**
     * Update Video in storage.
     *
     * @param  \App\Http\Requests\UpdateVideosRequest $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateVideosRequest $request, $id)
    {
        if (!Gate::allows('video_edit')) {
            return abort(401);
        }
        $request = $this->saveFiles($request);
        $video = Video::findOrFail($id);
        $video->update($request->all());


        $media = [];
        foreach ($request->input('video_id', []) as $index => $id) {
            $model = config('laravel-medialibrary.media_model');
            $file = $model::find($id);
            $file->model_id = $video->id;
            $file->save();
            $media[] = $file->toArray();
        }
        $video->updateMedia($media, 'video');

        return redirect()->route('admin.videos.index');
    }


    /**
     * Display Video.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!Gate::allows('video_view')) {
            return abort(401);
        }
        $video = Video::findOrFail($id);

        return $video->getMedia();//view('admin.videos.show', compact('video'));
    }


    /**
     * Remove Video from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!Gate::allows('video_delete')) {
            return abort(401);
        }
        $video = Video::findOrFail($id);
        $video->deletePreservingMedia();

        return redirect()->route('admin.videos.index');
    }

    /**
     * Delete all selected Video at once.
     *
     * @param Request $request
     */
    public function massDestroy(Request $request)
    {
        if (!Gate::allows('video_delete')) {
            return abort(401);
        }
        if ($request->input('ids')) {
            $entries = Video::whereIn('id', $request->input('ids'))->get();

            foreach ($entries as $entry) {
                $entry->deletePreservingMedia();
            }
        }
    }


    /**
     * Restore Video from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function restore($id)
    {
        if (!Gate::allows('video_delete')) {
            return abort(401);
        }
        $video = Video::onlyTrashed()->findOrFail($id);
        $video->restore();

        return redirect()->route('admin.videos.index');
    }

    /**
     * Permanently delete Video from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function perma_del($id)
    {
        if (!Gate::allows('video_delete')) {
            return abort(401);
        }
        $video = Video::onlyTrashed()->findOrFail($id);
        $video->forceDelete();

        return redirect()->route('admin.videos.index');
    }
}
