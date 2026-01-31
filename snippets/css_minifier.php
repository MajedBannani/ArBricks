<?php if(!defined('ABSPATH')) { die(); }  

add_shortcode('css-minifier', function($atts, $content = '') {
ob_start();
?><div class="container">
    <label class="ar-yt-ts__label" for="css-input">ضع أكواد CSS المراد تصغيرها هنا...</label>
    <textarea id="css-input" dir="ltr">
        </textarea>
        <label class="ar-yt-ts__label" for="css-output">هنا ستحصل على السخة المصغرة من كود CSS الخاص بك</label>
        <textarea id="css-output" readonly dir="ltr">
            </textarea>
            <button class="btn" id="minify-button">تصغير CSS الآن</button>
            </div>
            <style>

            #css-output, #css-input{
            width:100%;
            height:20rem;
            margin-bottom:var(--space-s);
            padding:var(--space-s);
            }
            #css-output::placeholder, #css-input::placeholder{
            color: var(--dark), #111;
            }
            </style>
            <script>
            document.getElementById("minify-button").addEventListener("click",function(){const e=document.getElementById("css-input").value,t=e.replace(/\/\*[\s\S]*?\*\//g,"").replace(/\s+/g," ").replace(/\s*{\s*/g,"{").replace(/\s*}\s*/g,"}").replace(/\s*;\s*/g,";").replace(/\s*:\s*/g,":").replace(/\s*,\s*/g,",").trim();document.getElementById("css-output").value=t});
            </script><?php
return ob_get_clean();

    }, 10);
