<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Redirect;
use Illuminate\Routing\Controller as BaseController;
use Storage;
use File;
use Carbon\Carbon;

class FileManagerController extends BaseController
{
    // Define the storage disk
    protected $disk = 'file_manager';
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!session()->has('user_id') || empty(session('user_id')) ||
            !session()->has('account_id') || empty(session('account_id')) ||
            !session()->has('role_id') || empty(session('role_id'))) {
                \Auth::logout();
                \Session::flush();
                return Redirect::to('/');
            }

            return $next($request);
        });
        $this->middleware('globalviewdata')->only(['index']);
    }

    public function index(Request $request)
    {
       
        
        $data = [
            'menu_name' => 'File Manager',
        ];
        return view('__app.components.filemanager',$data);
    }

    public function actions(Request $request)
    {
        $action = $request->input('action');
        $result = null;
        try{
        switch ($action) {
            case 'read':
                $result = $this->read($request);
                break;
            case 'details':
                $path = $request->input('path');
                $names = $request->input('names');
             
                $responseData = [
                    'details' => $this->getDetails($path, $names)
                ];
                break;
                break;
            case 'search':
                $result = $this->read($request);
                break;
            case 'create':
                $result = $this->create($request);
                break;
            case 'delete':
                $result = $this->delete($request);
                break;
            case 'rename':
                $result = $this->rename($request);
                break;
            case 'upload':
                $result = $this->upload($request);
                break;
            case 'download':
                $result = $this->download($request);
                break;
            default:
                return response()->json(['error' => 'Invalid action'], 400);
        }
        }catch(\Throwable $ex){
            aa($ex->getMessage());
            aa($ex->getTraceAsString());
        }
        aa($result);

        return response()->json($result);
    }
    
    protected function read(Request $request)
    {
        $path = $request->input('path', '/');
        $fullPath = $this->diskPath($path);
    
        $files = Storage::disk($this->disk)->files($fullPath);
        $directories = Storage::disk($this->disk)->directories($fullPath);
        
        $search_string = $request->input('searchString','');
        $search_string = trim($search_string,'*');
        if(!empty($search_string)){
            // Filter files and directories based on the search string
            $filteredFiles = array_filter($files, function ($file) use ($search_string) {
                return stripos(basename($file), $search_string) !== false;
            });
            $files = $filteredFiles;
        
            $filteredDirectories = array_filter($directories, function ($directory) use ($search_string) {
                return stripos(basename($directory), $search_string) !== false;
            });
            $directories = $filteredDirectories;
        }
        
        
        
        $filter_path = $path === '/' ? '' : "//".basename($path)."//";
        $cwd = [
            'name' => $path === '/' ? 'Root' : basename($path),
            'size' => 0,
            'dateModified' => $this->getDirectoryLastModifiedTime()->toIso8601String(),
            'dateCreated' => $this->getDirectoryLastModifiedTime()->toIso8601String(),
            'hasChild' => !empty($directories),
            'isFile' => false,
            'type' => '',
            'filterPath' => $filter_path
        ];
    
        $items = array_merge(
            array_map(function ($file) use ($path) {
                return [
                    'name' => basename($file),
                    'size' => Storage::disk($this->disk)->size($file),
                    'dateModified' => Carbon::createFromTimestamp(Storage::disk($this->disk)->lastModified($file))->toIso8601String(),
                    'dateCreated' => Carbon::createFromTimestamp(Storage::disk($this->disk)->lastModified($file))->toIso8601String(),
                    'hasChild' => false,
                    'isFile' => true,
                    'type' => '.' . pathinfo($file, PATHINFO_EXTENSION),
                    'filterPath' => $filter_path
                ];
            }, $files),
            array_map(function ($directory) use ($path) {
                return [
                    'name' => basename($directory),
                    'size' => 0,
                    'dateModified' => $this->getDirectoryLastModifiedTime($directory)->toIso8601String(),
                    'dateCreated' => $this->getDirectoryLastModifiedTime($directory)->toIso8601String(),
                    'hasChild' => true,
                    'isFile' => false,
                    'type' => '',
                    'filterPath' => $filter_path
                ];
            }, $directories)
        );
    
        return [
            'cwd' => $cwd,
            'files' => $items,
            'error' => null,
            'details' => null
        ];
    }
    
    protected function getDetails($path, $names)
    {
        $details = [];

        foreach ($names as $name) {
            $fullPath = $path . $name;
            if (Storage::disk($this->disk)->exists($fullPath)) {
                $isFile = Storage::disk($this->disk)->isFile($fullPath);
                $size = $isFile ? Storage::disk($this->disk)->size($fullPath) : 0;
                $created = Storage::disk($this->disk)->lastModified($fullPath);
                $modified = Storage::disk($this->disk)->lastModified($fullPath);

                $details[] = [
                    'name' => $name,
                    'location' => $fullPath,
                    'isFile' => $isFile,
                    'size' => $this->formatSizeUnits($size),
                    'created' => Carbon::createFromTimestamp($created)->format('n/j/Y g:i:s A'),
                    'modified' => Carbon::createFromTimestamp($modified)->format('n/j/Y g:i:s A'),
                    'multipleFiles' => false
                ];
            } else {
                return [
                    'cwd' => null,
                    'files' => null,
                    'error' => "File or directory does not exist: {$name}",
                    'details' => null
                ];
            }
        }

        return [
            'cwd' => null,
            'files' => null,
            'error' => null,
            'details' => $details[0] // Assuming single file for simplicity
        ];
    }

    protected function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
    
    
    protected function getDirectoryLastModifiedTime($directory = '/')
    {
        $files = Storage::disk($this->disk)->allFiles($directory);
    
        if (empty($files)) {
            return null; // Or handle empty directories as needed
        }
    
        $latestModificationTime = 0;
    
        foreach ($files as $file) {
            $modificationTime = Storage::disk($this->disk)->lastModified($file);
            if ($modificationTime > $latestModificationTime) {
                $latestModificationTime = $modificationTime;
            }
        }
    
        return Carbon::createFromTimestamp($latestModificationTime);
    }
    
    protected function diskPath($path)
    {
        return ltrim($path, '/');
    }


    protected function create(Request $request)
    {
        $path = $request->input('path', public_path());
        $name = $request->input('name', 'uploads');
        $type = $request->input('type', 'directory');

        if ($type === 'directory') {
            Storage::disk($this->disk)->makeDirectory($path . '/' . $name);
        } else {
            Storage::disk($this->disk)->put($path . '/' . $name, '');
        }

        return ['success' => true];
    }

    protected function delete(Request $request)
    {
        $path = $request->input('path', '');

        if (Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
            return ['success' => true];
        }

        return ['error' => 'File or directory does not exist'];
    }

    protected function rename(Request $request)
    {
        $oldPath = $request->input('path', '');
        $newPath = $request->input('newName', '');

        if (Storage::disk($this->disk)->exists($oldPath)) {
            Storage::disk($this->disk)->move($oldPath, $newPath);
            return ['success' => true];
        }

        return ['error' => 'File or directory does not exist'];
    }

    protected function upload(Request $request)
    {
        if ($request->hasFile('uploadFile')) {
            $file = $request->file('uploadFile');
            $path = $request->input('path', '');
            $filename = $file->getClientOriginalName();

            Storage::disk($this->disk)->putFileAs($path, $file, $filename);

            return ['success' => true];
        }

        return ['error' => 'No file uploaded'];
    }

    protected function download(Request $request)
    {
        $path = $request->input('path', '');

        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->download($path);
        }

        return ['error' => 'File does not exist'];
    }
}
