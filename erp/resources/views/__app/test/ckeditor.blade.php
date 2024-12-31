<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CKEditor Test</title>
   <script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <textarea id="editor" class="tinymce"></textarea>
    <script>
        function setCkEditor() {
            $("textarea.tinymce").each(function(i) {
                var textareaId = $(this).attr('id');
                if (textareaId) {
                    ClassicEditor
                        .create(document.querySelector("#" + textareaId), {   
                        })
                        .then(editor => {
                            console.log(`Editor initialized for textarea #${textareaId}`);
                        })
                        .catch(error => {
                            console.error(`Error initializing editor for textarea #${textareaId}`, error);
                        });
                } else {
                    console.warn('No ID found for textarea', this);
                }
            });
        }

        // Call the function to initialize editors
        $(document).ready(function() {
            setCkEditor();
        });
    </script>
</body>
</html>
