<?php if(!defined('ABSPATH')) { die(); }  

add_shortcode('qr-generator', function($atts, $content = '') {
ob_start();
?><script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>
<style>
.qr-generator {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: var(--space-s);
}

.qr-container__image-wrapper {
	width: 100%;
	max-width: 400px;
}
#qrCanvas {
	width: 100% !important;
	height: auto !important;
}

</style>
<div class="qr-generator">
	<p class="qr-generator__text">ادخل الرابط المطلوب تحويله ثم اضغط على ازراز انشاء الرمز:</p>
	<input lang="en" dir="ltr" class="qr-generator__input input" type="text" id="urlInput" placeholder="ادخل الرابط الذي تريد انشاء رمز QR له هنا">
	<br>
	<button class="qr-generator__btn btn" onclick="generateQRCode()">انشاء الرمز</button>
	<div class="qr-generator__image-wrapper">
		<canvas class="qr-generator__image" id="qrCanvas"></canvas>
        <div class="my-logo" id="mylogoImage" ></div>
	</div>
</div>
<script>

    function generateQRCode() {
	const url = document.getElementById("urlInput").value;
	if (!url) {
		alert("نرجو إدخال عنوان صالح");
		return;
	}
	const canvas = document.getElementById("qrCanvas");
	QRCode.toCanvas(
		canvas,
		url,
		{
			width: 400,
			height: 400
		},
		function (error) {
			if (error) {
				console.error(error);
				alert("عفواً حدث خطاء ما");
			}
		}
	);
}


</script><?php
return ob_get_clean();

    }, 10);
