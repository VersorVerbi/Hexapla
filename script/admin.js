$("#bigbox a").each(function() {
    $(this).attr('draggable', true);
    this.addEventListener("click", function(e) {
        if (!e.ctrlKey) {
            deselectAll();
        }
        $(this).toggleClass('selected');
        //console.log(e.target.innerText + " clicked");
    });
    this.addEventListener("dragstart", function(e) {
        e.dataTransfer.setData("text/plain", e.target.id);
    });
    this.addEventListener("dragover", function(e) {
        e.preventDefault();
    });
    this.addEventListener("drop", function(e) {
        e.preventDefault();
        let dragged = e.dataTransfer.getData("text");
        e.dataTransfer.clearData();
        let el_start = $("#" + dragged);
        let el_end = $(e.target);
        if (!el_start.hasClass('selected') && !el_end.hasClass('selected')) {
            deselectAll();
        }
        el_start.addClass('selected');
        el_end.addClass('selected');
        drawLineBetweenElements([el_start,el_end]);
    });
});

function drawLineBetweenElements(els) {
    let canvas = document.getElementById("cv");
    let ctx = canvas.getContext("2d");
    ctx.beginPath();
    let hp = horizontalPoint();
    let mps = $([]);
    els = $(els);
    els.each(function() {
        mps.push(getMidpoint(this));
    });
    //console.log(mps);
    mps.each(function() {
        ctx.moveTo(this.left, this.top);
        ctx.lineTo(this.left, hp);
        ctx.stroke();
    });
    let l = ultima(mps, -1);
    let r = ultima(mps, 1);
    ctx.moveTo(l, hp);
    ctx.lineTo(r, hp);
    ctx.stroke();
    if (l > 200 || r < 200) {
        ctx.moveTo(l, hp);
        ctx.lineTo(200, hp);
        ctx.stroke();
    }
    ctx.moveTo(200, hp);
    ctx.lineTo(200, 200);
    ctx.stroke();
}

function horizontalPoint() {
    let lastElementInEditor = $("#bigbox").children().last();
    let lastElementInEditorBottom = lastElementInEditor.position().top + lastElementInEditor.outerHeight();
    //console.log(lastElementInEditorBottom);
    return lastElementInEditorBottom + 5;
}

function getMidpoint(el) {
    let cvOffset = $("#cv").offset();
    let pos = $(el).offset();
    let mp = {'left': pos.left + ($(el).width() / 2.0), 'top': pos.top + ($(el).height() / 2.0)};
    mp.left = mp.left - cvOffset.left;
    mp.top = mp.top - cvOffset.top;
    //console.log("Canvas offset: 5x5; object offset: " + pos.left + "x" + pos.top + "; object width: " + $(el).width() + "; object height: " + $(el).height() + "; \"midpoint\": " + mp.left + "x" + mp.top);
    return mp;
}

function ultima(pointList, lr) {
    if (lr === 0) throw new Error();
    let ult = (lr > 0 ? 0 : 99999999999999);
    pointList.each(function() {
        if (lr < 0) {
            if (this.left < ult)
                ult = this.left;
        } else {
            if (this.left > ult)
                ult = this.left;
        }
    });
    return ult;
}

function deselectAll() {
    let selectedWords = $("a.selected");
    selectedWords.each(function() {
        $(this).removeClass('selected');
    });
    let canvas = document.getElementById("cv");
    canvas.getContext("2d").clearRect(0, 0, canvas.width, canvas.height);
}