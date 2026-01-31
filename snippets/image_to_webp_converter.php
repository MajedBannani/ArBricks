<?php if(!defined('ABSPATH')) { die(); }  

add_shortcode('webp-converter', function($atts, $content = '') {
ob_start();
?><style>
    .webp-container {
	display: flex;
    flex-direction: column;
    gap: var(--space-s);
}

input[type="file"] {
	margin-bottom: var(--space-s);
}

.webp-container__btn:disabled {
	background-color: #ccc;
	cursor: not-allowed;
}

.webp-container__output-wrapper {
	margin-top: var(--space-s);
	display: none;
}
</style>

<div class="webp-container">
	<p class="webp-container__text">قم بتحميل ما يصل إلى 20 صورة لتحويلها إلى تنسيق WebP وتنزيلها كملف ZIP.</p>
	<input class="webp-container__input input" type="file" id="fileInput" accept="image/png" multiple />
	<button class="webp-container__btn btn" id="convertButton" disabled>تحويل الصور</button>
	<div class="webp-container__output-wrapper output" id="output">
		<p class="webp-container__output-text" id="status"></p>
	</div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
    const fileInput = document.getElementById("fileInput");
const convertButton = document.getElementById("convertButton");
const status = document.getElementById("status");
const output = document.getElementById("output");
const downloadLink = document.createElement("a");

downloadLink.textContent = "Download ZIP";
downloadLink.style.display = "none";
output.appendChild(downloadLink);

const allowedExtensions = ["image/png", "image/jpeg", "image/jpg", "image/gif"];
const maxFileSize = 5 * 1024 * 1024; // 5 MB
const webpQuality = 0.8; // 80% quality

fileInput.addEventListener("change", () => {
	const files = Array.from(fileInput.files);
	const invalidFiles = files.filter(
		(file) => !allowedExtensions.includes(file.type) || file.size > maxFileSize
	);

	if (invalidFiles.length > 0) {
		convertButton.disabled = true;
		status.textContent = `Some files are invalid. Allowed formats: PNG, JPG, JPEG, GIF. Max size: 5 MB each.`;
	} else if (files.length > 20) {
		convertButton.disabled = true;
		status.textContent = "يمكنك تحميل ما يصل إلى 20 ملفًا كحد أقصى.";
	} else {
		convertButton.disabled = false;
		status.textContent = `${files.length} valid file(s) selected.`;
	}
});

convertButton.addEventListener("click", async () => {
	const files = Array.from(fileInput.files);
	const zip = new JSZip();
	const webpFolder = zip.folder("webp_images");

	status.textContent = "جاري تحويل الصور، الرجاء الإنتظار...";
	for (const file of files) {
		if (allowedExtensions.includes(file.type) && file.size <= maxFileSize) {
			const webpBlob = await convertToWebP(file);
			if (webpBlob) {
				const webpFileName = file.name.replace(/\.(png|jpg|jpeg|gif)$/i, ".webp");
				webpFolder.file(webpFileName, webpBlob);
			}
		}
	}

	status.textContent = "جاري إنشاء ملف ZIP...";
	const zipBlob = await zip.generateAsync({ type: "blob" });

	// Update the download link with the generated ZIP
	const url = URL.createObjectURL(zipBlob);
	downloadLink.href = url;
	downloadLink.download = "الصور-المحولة.zip";
	downloadLink.style.display = "inline";

	status.textContent =
		"تم التحويل بنجاح! انقر على الرابط أدناه لتنزيل ملف ZIP الخاص بك.";
	output.style.display = "flex";
});

function convertToWebP(file) {
	return new Promise((resolve) => {
		const reader = new FileReader();
		reader.onload = () => {
			const img = new Image();
			img.onload = () => {
				const canvas = document.createElement("canvas");
				canvas.width = img.width;
				canvas.height = img.height;
				const ctx = canvas.getContext("2d");
				ctx.drawImage(img, 0, 0);
				canvas.toBlob(
					(blob) => {
						resolve(blob);
					},
					"image/webp",
					webpQuality // Set the WebP quality to 80%
				);
			};
			img.src = reader.result;
		};
		reader.readAsDataURL(file);
	});
}

</script><?php
return ob_get_clean();

    }, 10);
