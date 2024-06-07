document.addEventListener('DOMContentLoaded', function () {
    console.log("Fields before query: ", document.querySelectorAll('.nerd_ai').length);

    document.querySelectorAll('.nerd_ai').forEach(function(field, index) {
        console.log("Processing field " + index, field);

        const button = document.createElement('button');
        button.innerText = 'Generate AI Text';
        button.onclick = function() {
            const prompt = field.value;
            $.request('onGenerateText', {
                data: { prompt: prompt },
                success: function (data) {
                    field.value = data.text;
                }
            });
        };

        field.parentElement.appendChild(button);
    });
});
