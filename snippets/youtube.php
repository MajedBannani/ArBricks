<?php if(!defined('ABSPATH')) { die(); }  

add_shortcode('youtube-generator', function($atts, $content = '') {
ob_start();
?><!-- css style start -->
<style>
    .ar-yt-ts__form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .ar-yt-ts__btn {
        cursor: pointer;
    }

    .ar-yt-ts__output-wrapper {
        margin-top: var(--space-s);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--space-s);
    }

    .ar-yt-ts__output-text {
        text-align: center;
    }
</style>
<!-- css style end -->

<!-- html start -->
<div class="ar-yt-ts">
    <h2 class="ar-yt-ts__title">
        منشئ رابط مشاركة يوتيوب في وقت محدد من الفيديو
    </h2>
    <form class="ar-yt-ts__form" id="timestampForm">
        <label class="ar-yt-ts__label" for="videoId"
            >معرف الفيديو من يوتيوب:</label
        >
        <input
            class="ar-yt-ts__input input"
            type="text"
            id="videoId"
            placeholder="أدخل معرف الفيديو هنا"
            required
        />

        <label class="ar-yt-ts__label" for="timestamp"
            >الوقت المطلوب بداية الفيديو منه:</label
        >
        <input
            class="ar-yt-ts__input input"
            type="text"
            id="timestamp"
            placeholder="ادخل الوقت هنا مثلاً 2:32"
            required
        />

        <label class="ar-yt-ts__label" for="description"
            >وصف بداية هذا الوقت (إختياري):</label
        >
        <input
            class="ar-yt-ts__input input"
            type="text"
            id="description"
            placeholder="ادخل الوصف هنا"
        />

        <button class="ar-yt-ts__btn btn" type="submit">إنشاء الرابط</button>
    </form>

    <div class="ar-yt-ts__output-wrapper" id="output" style="display: none;">
        <h3 class="ar-yt-ts__output-title">الرابط المحول:</h3>
        <div class="ar-yt-ts__output-text" id="generatedLink"></div>
        <button class="ar-yt-ts__output-link btn" id="copyLink">
            نسخ الرابط
        </button>
    </div>
</div>
<!-- html end -->

<!-- js start -->
<script>
    document.getElementById("timestampForm").addEventListener("submit", (e) => {
        e.preventDefault();

        const videoId = document.getElementById("videoId").value.trim();
        const timestamp = document.getElementById("timestamp").value;
        const description = document.getElementById("description").value;
        const timeInSeconds = convertToSeconds(timestamp);

        if (videoId && timeInSeconds !== null) {
            const generatedLink = `https://www.youtube.com/watch?v=${videoId}&t=${timeInSeconds}s`;
            const outputContent = description
                ? `<strong>${description}:</strong><br><a href="${generatedLink}" target="_blank">${generatedLink}</a>`
                : `<a href="${generatedLink}" target="_blank">${generatedLink}</a>`;

            document.getElementById("generatedLink").innerHTML = outputContent;
            document.getElementById("output").style.display = "flex";
        } else {
            alert("نرجو إدخال معرف صحيح والوقت بصيغة صحيحة (على سبيل المثال, 1:23 او 0:45)");
        }
    });

    document.getElementById("copyLink").addEventListener("click", () => {
        const generatedLinkText = document.getElementById("generatedLink").textContent;
        navigator.clipboard.writeText(generatedLinkText).then(() => {
            alert("تم النسخ بنجاح");
        });
    });

    function convertToSeconds(time) {
        const parts = time.split(":").map(Number);
        if (parts.length === 2) {
            return parts[0] * 60 + parts[1];
        } else if (parts.length === 3) {
            return parts[0] * 3600 + parts[1] * 60 + parts[2];
        }
        return null;
    }
</script>
<!-- js end --><?php
return ob_get_clean();

    }, 10);
