/**
 * 勤怠画面の時間表示をリアルタイムで更新
 */
(function () {
    "use strict";

    /**
     * 現在の日時を取得してフォーマット
     */
    function getCurrentDateTime() {
        const now = new Date();

        // 曜日名の配列
        const weekdayNames = ["日", "月", "火", "水", "木", "金", "土"];

        // 日付のフォーマット（2023年6月1日(木)）
        const year = now.getFullYear();
        const month = now.getMonth() + 1;
        const day = now.getDate();
        const weekday = weekdayNames[now.getDay()];
        const date = `${year}年${month}月${day}日(${weekday})`;

        // 時刻のフォーマット（08:00）
        const hours = String(now.getHours()).padStart(2, "0");
        const minutes = String(now.getMinutes()).padStart(2, "0");
        const time = `${hours}:${minutes}`;

        return { date, time };
    }

    /**
     * 時間表示を更新
     */
    function updateTimeDisplay() {
        const { date, time } = getCurrentDateTime();

        // 日付要素を更新
        const dateElement = document.getElementById("attendance-date");
        if (dateElement) {
            dateElement.textContent = date;
        }

        // 時刻要素を更新
        const timeElement = document.getElementById("attendance-time");
        if (timeElement) {
            timeElement.textContent = time;
        }
    }

    /**
     * 初期化
     */
    function init() {
        // ページ読み込み時に即座に更新
        updateTimeDisplay();

        // 1秒ごとに更新
        setInterval(updateTimeDisplay, 1000);
    }

    // DOMContentLoadedイベントで初期化
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();

