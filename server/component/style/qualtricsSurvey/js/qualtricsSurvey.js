$(document).ready(function () {
    initQualtricsSurvey();
});

function initQualtricsSurvey() {
    $("iframe").on('load', function () {
        iFrameResize({
            log: false,
            heightCalculationMethod: 'taggedElement'
        });
    });
}