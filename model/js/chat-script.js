var baseURL = "http://kamart.ir/wp-content/plugins/hdtsChat/";
var imgURL = "";
var emoji_view_div ;
var emojiAdded=false;
var text_view;
var cf;
var no_msg_set_yet = "HDTSNOTSETYET";
var recentMsg = "";
var canLoadMsg = true;
var userId = "-1";
var userAgent = "";
var userPublicIp = "none";
var sendingImage =  false;
var recentMsgCount = -1;

function getIpAddress() {
    $.get('https://ipinfo.io/ip', function(data,status,xhr) {
        console.log("ip = "+data);
        userPublicIp =  data;
        continueOnReady();
    })
}

function getUserFromServerIfPossible() {
    var location = window.location;
    $.get(baseURL+"model/php/function.php?f=getMIFS&agent="
        +userAgent + "&userPip="+userPublicIp + "&locate=" + location
        ,function (data,status,xhr){
        if(data != "false"){
            if(userId != data){
                userId = data;
                console.log("id : " + data);
    			continueOnReady();
                getMessagesFromServer();
            }
        }
    })

}

function getLocalIp() {
    var localIp = "192.168.43.92";
    console.log("Couldn't connect to server IP is " + localIp);
    userPublicIp =  localIp;
	getUserFromServerIfPossible();
}

$(document).ready(function (){
    userAgent = navigator.userAgent;
    getIpAddress();
   // getLocalIp();
});

function continueOnReady(){
    
    addEmoji();
    $('#btn-open-chat').show();
    if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
        document.getElementById("div-chat-dialog").style.width = "100%";
        document.getElementById("div-chat-dialog").style.borderRadius = "0px";
        document.getElementById("div-chat-dialog").style.height = "100%";
        document.getElementById("div-chat-dialog").style.maxHeight = "100%";
        document.getElementById("div-chat-dialog").style.maxWidth = "100%";
    }else{
        document.getElementById("div-chat-dialog").style.bottom = "4px";
        document.getElementById("div-chat-dialog").style.left = "4px";
    }

    window.setInterval(function(){
        //Local Test : //
        getUserFromServerIfPossible();
        //test Not local
        //getIpAddress();
        getMessagesFromServer();
    }, 5000);


    text_view = document.getElementById("text-area-input-text");
    $("#text-area-input-text").keypress(function (event){
        if (event.keyCode === 13) {
            event.preventDefault();
            sendMsg();
        }
    });
    $("#text-area-input-text-upload-image").keypress(function (event){
        if (event.keyCode === 13) {
            document.getElementById("agree").click();
        }
    });
    $(document).keyup(function (event){
        if (event.keyCode === 27) {
            event.preventDefault();
            if( emoji_view_div != null && emoji_view_div.style.display != "none"){
                //closeEmoji();
            }else{
                //  closeForm();
            }
        }
    });

}

function change_dialog_display(view,display){
    document.getElementById("div-chat-dialog").style.display = display;
}
function openForm(view) {
    change_dialog_display(view,"block");
    scrollEnd("none");
    document.getElementById("text-area-input-text").focus();
}
function closeForm() {
    change_dialog_display(null,"none");
}

function openChooser(){
    document.getElementById("real-upload-btn").click();
}

function setDefaultToPreviewDialog(){
    document.getElementById("agree-loader").style.display = "none";
    document.getElementById("upload-img-dialog-close").style.display = "none";
    document.getElementById("agree").style.display = "block";
    document.getElementById("not-agree").style.display = "block";
    // document.getElementById("text-area-input-text-upload-image").value = "";


}
function showImgPreviewDialog(){
    setDefaultToPreviewDialog();
    document.getElementById("upload-img-dialog").style.display = "block";
    document.getElementById("text-area-input-text-upload-image").focus();

}
function hideImgPreviewDialog(){
    document.getElementById("upload-img-dialog").style.display = "none";
}

function sendImageToDatabase(cf,msg,type){
    preImageSend();
    requestImageSend(cf,msg,type);
}

function failImageSend(){
    document.getElementById("agree-loader").style.display = "none";
    document.getElementById("upload-img-dialog-close").style.display = "block";
    // document.getElementById("text-area-input-text-upload-image").style.display = "none";
    document.getElementById("sure-to-send-image").textContent = "خطا لطفا بعدا امتحان کنید.";
}



function requestImageSend(cf,msg,type){
    var extra = "";
    if(type == "msi"){
        extra = "sendMSI&msg="+msg;
    }else if(type == "img"){
        extra = "sendImg";
    }
     if(!sendingImage){
        $.ajax({
            url: baseURL+"model/php/function.php?f="+extra+"&from="+userId+"&to=admin"+"&locate="+window.location,
            type: "POST",
            data: cf,
            contentType : false,
            cache: false,
            processData: false,
            success : function (data){
                console.log(data);
                if(!sendingImage) {
                    sendingImage = true;
                    successImageSend(msg, data['img'])
                }
            },
            error:function (data){
                console.log(data);
            }
        })
     }
}

function successImageSend(msg,image){
    hideImgPreviewDialog();
    addImgToMsgView(msg,image);
}

function checkToRemoveNoMessage() {
    var loading = docu("messages-not-load-yet");
    var noMessage = docu("no-message-found");
    if(loading != null && loading.style.display != "none"){
    loading.style.display = "none";
    }
    if(noMessage != null &&noMessage.style.display != "none"){
        noMessage.style.display = "none";
    }
}

function addImgToMsgView(msg,imgUrl){
    sendingImage = false;
    var i = 0;
    while(document.getElementById("loader-"+msg+i) != null){
        i++;
        // alert(msg + i);
    }
    var newMsg;
    if(msg == no_msg_set_yet){
        newMsg = '<div class="message right">' +
            '<div class="message-view">' +
            '<img class="message-image" onclick="openClickedImage(this);" src="'+imgUrl+'"/>'+
            '<div class="message-content" >' +
            '<div class="loader" style="display: block;"></div>' +
            '<img class="status" style="display: none;" src="http://kamart.ir/wp-content/plugins/hdtsChat/image/ic_sent.png"/>' +
            '</div>' +
            '</div>' +
            '</div>'
    }else{
        newMsg = "<div class='messages right' style='max-width: 60%;' >" +
            "<div class='message-view'>" +
            "<img class='message-image' onclick='openClickedImage(this);' src='"+imgUrl+"'/>"+
            "<div class='message-content' style='float: right;'>" +
            "<div class='message-text' >"+msg+"</div>" +
            "<div id='loader-"+msg+i+"' class='loader' style='display: block;'></div>" +
            "<img id='status-"+msg+i+"' class='status' style='display: none;' src='http://kamart.ir/wp-content/plugins/hdtsChat/image/ic_sent.png'/>" +
            "</div>" +
            "</div>" +
            "</div>";
    }
    checkToRemoveNoMessage();
    document.getElementById("div-chat-view").innerHTML +=  newMsg;
    closeEmoji();
    scrollEnd("animate");
}

function preImageSend(){
    document.getElementById("agree-loader").style.display = "block";
    document.getElementById("sure-to-send-image").textContent = "لطفا صبر کنید .....";
    document.getElementById("agree").style.display = "none";
    document.getElementById("not-agree").style.display = "none";
    // document.getElementById("text-area-input-text-upload-image").style.display = "none";

}

function checkToUploadImage(){
    if(!sendingImage){
        if(document.getElementById("text-area-input-text-upload-image").value != ""){
            type = "msi";
            msg = document.getElementById("text-area-input-text-upload-image").value;
            document.getElementById("text-area-input-text-upload-image").value = "";
        }else{
            type = "img";
            var msg = no_msg_set_yet ;
        }
        console.log("ready to upload");
        sendImageToDatabase(cf,msg,type);
    }
}

function getFileName() {
    const realBtn = document.getElementById("real-upload-btn");
    cf = new FormData();
    if(realBtn.value){
        var image = realBtn.files[0];
        var imageName = image.name;
        var imageExtension = imageName.split('.').pop().toLowerCase();
        var imageSize = image.size;
        if(jQuery.inArray(imageExtension,['png','jpeg','jpg']) != -1){
            if(imageSize <= 2000000){
                if(realBtn.value.match(/[\/\\]([\w\d\s\.\-\(\)]+)$/) == null){
                    alert("نام عکس نمیتوان فارسی باشد");
                    return "Error";
                }
                var file_name = realBtn.value.match(/[\/\\]([\w\d\s\.\-\(\)]+)$/)[1];
                cf.append("file",image);
                formData = cf;
                hasImage = true;
                image_name = file_name;
                showImgPreviewDialog();
                var sure_to_send = "آیا از فرستادن  " + image_name + "  مطمئنی؟";
                var type = "";
                document.getElementById("sure-to-send-image").textContent = sure_to_send;
            }else{
                alert("حجم فایل خیلی زیاده!");

            }
        }else{
            alert("فرمت فایل نا معتبر!");

        }
    }else{

    }
    realBtn.value = "";
}

function sendMsg(){
    var msg = document.getElementById("text-area-input-text").value;
    document.getElementById("text-area-input-text").value = "";
    if(msg.trim() != "")
        addMsgToMsgView(msg);
}

function getMessagesFromServer(){
    if(canLoadMsg){
        canLoadMsg = false;
        $.post(baseURL+"model/php/function.php",{f : "getMsg" ,by : userId},function (data,status,xhr){
            console.log(status);
            setMsgView(data);
        })
    }
}

function removeOldData(recentMsg, allMsg) {

}

function checkToVisibleNoMessage() {
    var loading = docu("messages-not-load-yet");
    var noMessage = docu("no-message-found");
    if(loading != null && loading.style.display != "none"){
        loading.style.display = "none";
    }
    if(noMessage.style.display != "block"){
        noMessage.style.display = "block";
    }
}

function setMsgView(allMsg){

    var objs = JSON.parse(allMsg);
    var msgData = objs['msg'];
    var msgCount = objs['count'];
    var data = "";
    if(msgCount == 0){
        checkToVisibleNoMessage()
    }
    if(recentMsg != msgData){
        recentMsg = msgData;

        document.getElementById("div-chat-view").innerHTML = msgData;
        if(recentMsgCount != msgCount){
            scrollEnd("none");
        }
        recentMsgCount = msgCount;
    }else{
    }

    canLoadMsg = true;
}

function onRequestMsgResponse(i, data, status) {
    if(status == "success" && data == "success"){
        canLoadMsg = true;
        docu("loader-"+i).style.display = "none";
        docu("status-"+i).style.display = "block";
    }else if(status == "success" && data == "error"){
        console.log("error while sending data");
    }
}

function requestSendMsg(msg , i) {
    canLoadMsg = false;
    var location = window.location;
    $.post(baseURL+"model/php/function.php?locate="+location,{f : "sendMsg",msg : msg , by : userId ,type : "msg", usr : "1" },function (data,status,xhr){
        onRequestMsgResponse(i,data,status);
    })
}

function addMsgToMsgView(msg){
    var i = 0;
    while(document.getElementById("loader-"+msg+i) != null){
        i++;
        // alert(msg + i);
    }

    var newMsg =
        "<div class='message right'>" +
        "<div class='message-view' >" +
        "<div class='message-content'>" +
        "<div class='message-text' >"+msg+"</div>" +
        "<div id='loader-"+msg+i+"' class='loader' style='display: block;'></div>" +
        "<img id='status-"+msg+i+"' class='status' style='display: none;' src='http://kamart.ir/wp-content/plugins/hdtsChat/image/ic_sent.png'/>" +
        "</div>" +
        "</div>"+
        "</div>";
    checkToRemoveNoMessage();
    document.getElementById("div-chat-view").innerHTML +=  newMsg;
    closeEmoji();
    scrollEnd("animate");
    requestSendMsg(msg,i);
    
}

function onMsgSendResponse(msg,i,res){
    testView(msg,i,res);
    // alert(res);
    // alert(msg);
}
function testView(msg,i,res){
    var loading = document.getElementById("loader-"+msg+i);
    var status = document.getElementById("status-"+msg+i);
    if(msg === "fail"){
        loading.style.display = "none";
        status.style.display = "block";
        status.src = baseURL+"image/ic_failed.png";
    }else if(msg === "success"){
        loading.style.display = "none";
        status.style.display = "block";
        status.src = baseURL+"image/ic_sent.png";
    }else if(msg === "loading"){
        loading.style.display = "block";
        status.style.display = "none";
    }else{
        if(res == "works"){
            loading.style.display = "none";
            status.style.display = "block";
            status.src = baseURL+"image/ic_sent.png";
        }else if(res == "error"){
            loading.style.display = "none";
            status.style.display = "block";
            status.src = baseURL+"image/ic_failed.png";
        }
    }
}
function scrollEnd(animation){
    var d = $('#div-chat-view');
    if(d.scrollTop() == d.prop("scrollHeight")){

    }else{
        if(animation == "none"){
            d.scrollTop(d.prop("scrollHeight"));
        }else if(animation == "animate"){
            d.animate({ scrollTop: d.prop("scrollHeight")}, 90);
        }
    }
}

function onInputViewEnterKeyPressed(view){
    alert(view.code);
}

function checkEmojiView() {
    emoji_view_div = document.getElementById("emoji-div");
    if(emoji_view_div.style.display == "block"){
        closeEmoji();
    }else{
        openEmoji();
    }
}



function addEmoji(){
    emojiAdded = true;
    var emojRange = [
        [128513, 128591], [9986, 10160], [128640, 128704]
    ];
    var emojies;
    for (var e = 0; e < emojRange.length; e++) {
        var range = emojRange[e];
        for (var x = range[0]; x < range[1]; x++) {
            var cem = "&#" + x + ";";
            emojies = "<div class='emoji-item' onclick='onEmojiItemClicked(this);'>"+cem+"</div>";
            if(emojies != "undefined")
                document.getElementById("emoji-view").innerHTML += emojies;
        }

    }
    // document.getElementById("loading-view-of-emoji").style.display = "none";
}
function onEmojiItemClicked(elem){
    var ce = elem.textContent;
    if(text_view != null){
        text_view.value += ce;
        text_view.focus();
    }

}




function closeSentImagePreview(){
    $('#preview-sent-image').hide();
}
function showSentImagePreview(imgSrc){
    docu("img-sent").src =imgSrc;
    $('#preview-sent-image').show();
}
function openClickedImage(img){
    showSentImagePreview(img.src);
}

function openEmoji(){
    //if(!emojiAdded)
    // addEmoji();

    emoji_view_visible(true);
}
function closeEmoji(){
    emoji_view_visible(false);
}
function emoji_view_visible(state) {
    if(state) {
        if (emoji_view_div != null && emoji_view_div.style.display != "block" ) {
            emoji_view_div.style.display = "block";
            document.getElementById("emoji-button").style.padding = "4px 6px";
        }
    }else{
        if( emoji_view_div != null && emoji_view_div.style.display != "none"){
            emoji_view_div.style.display = "none";
            document.getElementById("emoji-button").style.padding = "2px 4px";
        }
    }
}
function docu(id){
    return document.getElementById(id);
}