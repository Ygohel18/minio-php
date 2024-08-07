<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Upload</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Upload an Image</h1>
    <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="image">Choose an image:</label>
            <input type="file" class="form-control-file" id="image" name="image" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
    <hr>
    <div id="results" class="mt-4"></div>
</div>

<script>
    document.getElementById('uploadForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent default form submission

        const formData = new FormData(this);

        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<h2>Uploaded Images</h2>';

            data.forEach(image => {
                resultsDiv.innerHTML += `
                    <div class="mb-3">
                        <h3>${image.size}</h3>
                        <p><strong>Object Key:</strong> ${image.key}</p>
                        <p><strong>Permanent URL:</strong> <a href="${image.url}" target="_blank">${image.url}</a></p>
                        <img src="${image.url}" class="img-fluid" alt="Uploaded Image">
                    </div>`;
            });
        })
        .catch(error => console.error('Error:', error));
    });
</script>
</body>
</html>
