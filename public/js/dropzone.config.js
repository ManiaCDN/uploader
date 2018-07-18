// Thanks to https://j11y.io/javascript/bujs-1-getparameterbyname/
function getParameterByName(name) {
    var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
    return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
}

// use the camelized version of HTML element id
Dropzone.options.uploadBrowse = {
    // The name that will be used to transfer the file
    paramName: "file",
    
    maxFilesize: 512, // MB
    
    // Timeout after which the upload should be aborted. Default is 30000.
    // Given in microseconds. Does not trigger an error on timeout...
    timeout: 86400000,
    
    params: {
        // pass the GET path variable to the serverside upload function,
        // so it knows where to store the file.
        path: getParameterByName('path'),
    }
};