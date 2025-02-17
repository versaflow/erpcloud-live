<?php


function file_manager_syncfusion_file_operations(){
    /*
    read	Read the details of files or folders available in the given path from the file system, to display the files for the user to browse the content.
    create	Creates a new folder in the current path of the file system.
    delete	Removes the file or folder from the file server.
    rename	Rename the selected file or folder in the file system.
    search	Searches for items matching the search string in the current and child directories.
    details	Gets the detail of the selected item(s) from the file server.
    copy	Copy the selected file or folder in the file system.
    move	Cut the selected file or folder in the file server.
    upload	Upload files to the current path or directory in the file system.
    download	Downloads the file from the server and the multiple files can be downloaded as ZIP files.
    */
}


function file_manager_get_directories(){
    $directories = Storage::disk('file_manager')->allDirectories();
    return $directories;
}

function file_manager_save_file($directory, $filename, $file_content){
    
 
    Storage::disk('file_manager')->put($directory.'/'.$filename, $file_content);
}

function file_manager_get_files($directory){
    // Get all files in the specified directory
 
    $files = Storage::disk('file_manager')->files($directory);
    return $files;
}

function file_manager_get_folder_details($directory){

   ;
    $size = 0;
    $files = Storage::disk('file_manager')->allFiles($directory);
    
    foreach ($files as $file) {
        $size += Storage::disk('file_manager')->size($file);
    }

    return ['size' => $size,'count' =>count($files)];
}

function file_manager_path()
{
    $instance_dir = session('instance')->directory;
    $path = storage_path('file_manager').'/'.$instance_dir.'/';
  
    return $path;
}

