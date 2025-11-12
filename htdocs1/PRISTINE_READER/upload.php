<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Upload Data</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>PRISTINE Uploader</h1>
        </header>

        <div class="upload-container">
            <form id="davao-form" class="upload-form" enctype="multipart/form-data">
                <h2>Davao Data</h2>
                <div class="drop-zone" id="davao-drop-zone">
                    <span class="drop-zone-prompt">Drag & Drop files here or click to select</span>
                    <p>Required files: Dav_PRISTINE_ORDER.CSV, Dav_PRISTINE_ORDER_BYROUTE.CSV, Dav_PRISTINE_ORDER_BYSKU.CSV</p>
                    <input type="file" name="davao_files[]" class="drop-zone-input" multiple>
                </div>
                <div class="file-list" id="davao-file-list"></div>
                <button type="submit" class="upload-btn">Upload Davao Data</button>
            </form>

            <form id="gensan-form" class="upload-form" enctype="multipart/form-data">
                <h2>Gensan Data</h2>
                <div class="drop-zone" id="gensan-drop-zone">
                    <span class="drop-zone-prompt">Drag & Drop files here or click to select</span>
                     <p>Required files: Gen_PRISTINE_ORDER.CSV, Gen_PRISTINE_ORDER_BYROUTE.CSV, Gen_PRISTINE_ORDER_BYSKU.CSV</p>
                    <input type="file" name="gensan_files[]" class="drop-zone-input" multiple>
                </div>
                 <div class="file-list" id="gensan-file-list"></div>
                <button type="submit" class="upload-btn">Upload Gensan Data</button>
            </form>
        </div>
        <div id="status-message"></div>
    </div>

    <script src="upload_script.js"></script>
</body>
</html>