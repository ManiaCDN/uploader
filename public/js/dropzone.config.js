// Thanks to https://j11y.io/javascript/bujs-1-getparameterbyname/
function getParameterByName(name) {
    var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
    return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
}

// use the camelized version of HTML element id
Dropzone.options.uploadBrowse = {
    paramName: "file", // The name that will be used to transfer the file
    maxFilesize: 512, // MB
    params: {
        path: getParameterByName('path'),
    }
};