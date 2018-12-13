//сериализация данных формы в объект
$.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

function loadFile(data) {
    if (!data)
        return false;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.location.pathname, true);
    xhr.responseType = 'arraybuffer';
    BX.showWait();
    xhr.onload = function () {
        BX.closeWait();
        if (this.status === 200) {

            var filename = "";
            var disposition = xhr.getResponseHeader('Content-Disposition');
            if (disposition && disposition.indexOf('attachment') !== -1) {
                var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                var matches = filenameRegex.exec(disposition);
                if (matches != null && matches[1])
                    filename = matches[1].replace(/['"]/g, '');
            }
            var type = xhr.getResponseHeader('Content-Type');

            var blob = new Blob([this.response], {type: type});

            if (typeof window.navigator.msSaveBlob !== 'undefined') {

                window.navigator.msSaveBlob(blob, filename);
            } else {
                var URL = window.URL || window.webkitURL;
                var downloadUrl = URL.createObjectURL(blob);

                if (filename) {
                    var a = document.createElement("a");
                    if (typeof a.download === 'undefined') {
                        window.location = downloadUrl;
                    } else {
                        a.href = downloadUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                    }
                } else {
                    window.location = downloadUrl;
                }

                setTimeout(function () {
                    URL.revokeObjectURL(downloadUrl);
                }, 100); // cleanup

            }
        }
    };
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.send($.param(data));
}

function sendAjaxStep(data) {
    if (!data)
        return false;
    BX.showWait();
    $.ajax({
        url: window.location.pathname,
        data: data,
        type: "POST",
        dataType: "json",
        success: function (response) {
            console.log(response);
            BX.closeWait();
            if (response) {
                if (response.hash)
                    data["hash"] = response.hash;
                if (response.complete === false) { //только если false, чтобы не зацикливался при отсутствии ответа
                    sendAjaxStep(data);
                } else if (response.complete == true) {
                    loadFile(data);
                }
            }
        }
    });
}

$(function () {
    $(".users-list").each(function () {
        $(document, this).on("click", ".forms-wrapper button", function (e) {
            e.preventDefault();
            var $form = $(this).closest("form");
            var data = $form.serializeObject();
            sendAjaxStep(data);
        });
    });
});