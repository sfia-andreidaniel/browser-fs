function BFS_cmd_file_delete( app ) {

    
app.handlers.cmd_file_delete = function() {

    var $namespace = {};

    var $export = function(objectID, objectData) {
        $namespace[objectID] = objectData;
        return objectData;
    };
    var $import = function(objectID) {
        return $namespace[objectID] || (function() {
            throw "Namespace " + objectID + " is not defined (yet?)";
        })();
    };
    var $pid = getUID();

    var dlg = $export("0001-dlg", (new Dialog({
        "alwaysOnTop": false,
        "appIcon": "",
        "caption": "Confirm item(s) deletion",
        "closeable": true,
        "height": 231,
        "maximizeable": true,
        "maximized": false,
        "minHeight": 50,
        "minWidth": 50,
        "minimizeable": true,
        "modal": false,
        "moveable": true,
        "resizeable": true,
        "scrollable": false,
        "visible": true,
        "width": 396,
        "x": 8,
        "y": -11
    })).chain(function() {
        Object.defineProperty(this, "pid", {
            "get": function() {
                return $pid;
            }
        });
        this.addClass("PID-" + $pid);
    }));

    $export("0001-img", (new DOMImage({
        "src": "data:image/png;base64,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",
        "displayMode": "best"
    })).setAttr("style", "top: 20px; left: 10px; width: 48px; height: 48px; position: absolute; border-color: transparent"));

    $export("0001-lbl", (new DOMLabel("Are you sure you want to delete 5 item(s) together with all their contents?")).setAttr("style", "top: 30px; left: 70px; position: absolute; text-overflow: ellipsis; font-weight: bold; height: 30px; white-space: normal").setAnchors({
        "width": function(w, h) {
            return w - 80 + "px";
        }
    }));

    $export("0002-lbl", (new DOMLabel("Operation cannot be undone!")).setAttr("style", "top: 70px; left: 70px; position: absolute; text-overflow: ellipsis").setAnchors({
        "width": function(w, h) {
            return w - 90 + "px";
        }
    }));

    $export("0001-btn", (new Button("DELETE", (function() {}))).setAttr("style", "bottom: 10px; right: 10px; position: absolute"));

    $export("0001-progress", (new ProgressBar({
        "value": 0,
        "minValue": 0,
        "maxValue": 100,
        "captionFormat": "/v%"
    })).setAttr("style", "bottom: 50px; left: 10px; position: absolute; height: 16px").setAnchors({
        "width": function(w, h) {
            return w - 20 + "px";
        }
    }));

    $export("0003-lbl", (new DOMLabel("Waiting for user confirmation ...")).setAttr("style", "bottom: 75px; left: 10px; position: absolute; text-overflow: ellipsis").setAnchors({
        "width": function(w, h) {
            return w - 20 + "px";
        }
    }));

    $export("0002-btn", (new Button("Cancel", (function() {}))).setAttr("style", "bottom: 10px; right: 85px; position: absolute"));

    $import("0001-dlg").insert($import("0001-img"));
    $import("0001-dlg").insert($import("0001-lbl"));
    $import("0001-dlg").insert($import("0002-lbl"));
    $import("0001-dlg").insert($import("0001-btn"));
    $import("0001-dlg").insert($import("0001-progress"));
    $import("0001-dlg").insert($import("0003-lbl"));
    $import("0001-dlg").insert($import("0002-btn"));

    setTimeout(function() {
        dlg.paint();
        dlg.ready();
    }, 1);

    return dlg;

};


}