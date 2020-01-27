window.loadJsonFile = function (jsonFile, loadedCallback) {

    function loadJSON(callback) {

        var xobj = new XMLHttpRequest();
        xobj.overrideMimeType("application/json");
        xobj.open('GET', jsonFile, true);
        xobj.onerror = function(e) {
            alert("Error " + e.target.status + " occurred while receiving the document. Try to refresh the page");
        };
        
        xobj.onreadystatechange = function() {
            if (xobj.readyState == 4 && xobj.status == "200") {

                // .open will NOT return a value but simply returns undefined in async mode so use a callback
                callback(xobj.responseText);
            }
        }
        xobj.send(null);

    }

    var jsonLoaded = false;
    
    window.addEventListener("load", function(event) {
       loadJSON(function(response) {
           let data = JSON.parse(response);
           return loadedCallback(data);
       });
    });
};
