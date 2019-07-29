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
    
    // Allows cancelling the upload
    addRemoveLinks: true,
    
    // Center message in the dropzone
    dictDefaultMessage: "<strong>Drop File here<br /><u>Choose File</u></strong>",
    
    dictCancelUpload: "Cancel Upload",
    
    // don't show it because it might be confusing while not being so useful
    dictRemoveFile: "",
    
    params: {
        // pass the GET path variable to the serverside upload function,
        // so it knows where to store the file.
        path: getParameterByName('path'),
    },
    
    init: function() {
        this.on("success", function(file) {
            document.getElementById('upload_success_alert').style.display = 'block';
        });
    }
};