function toogleView(i, v) {
    var input = $(i);
    if (input.prop("checked") === false) {
        $(v).fadeOut();
    } else {
        $(v).fadeIn();
    }
}

window.isFocused = true;
function messageSent(c) {
    $('.chat-form input[type=text]').val('');
    $('.chats-container').append(c);
    scrollToBottom($('.chats-container'));
    reloadInit();
}
function go_welcomePage(step) {
    if (step === 4) {
        var users  = '';
        $('.each-welcome-user input').each(function() {
            if ($(this).prop('checked') !== false) {
                users += (users === '') ? $(this).val() : ','+$(this).val();
            }
        });
        pageLoader();
        $.ajax({
            url : buildLink('welcome/finish', [{key:'users', value: users}]),
            success : function(r) {
                pageLoaded();
                window.location.href = buildLink('');
            }
        });
    }
    $(".getstarted-content .step").hide();
    $(".getstarted-content .step-" + step).fadeIn();

    if (step === 3) {
        $("#getstarted-back").removeAttr('disabled');
        $("#getstarted-back").removeClass('disabled');
        $('.progress-bar').css('width', '50%');
        //get all selected genres
        var genres  = '';
        $('.each-genre input').each(function() {
            if ($(this).prop('checked') !== false) {
                genres += (genres === '') ? $(this).val() : ','+$(this).val();
            }
        });
        pageLoader();
        $.ajax({
            url : buildLink('welcome/load', [{key:'genres', value: genres}]),
            success : function(r) {
                pageLoaded();
                $("#getstarted-users").html(r);
            }
        });
    } else if(step === 2) {
        $("#getstarted-back").attr('disabled', 'disabled');
        $("#getstarted-back").prop('disabled', 'disabled');
    }
    return false;
}
function select_genre(id) {

    if ($(".each-genre-"+id + ' input').prop('checked') !== false ) {
        $(".each-genre-"+id + ' i').fadeOut();
        $(".each-genre-"+id + ' input').removeAttr('checked');
    } else {
        $(".each-genre-"+id + ' i').fadeIn();
        $(".each-genre-"+id + ' input').prop('checked', 'checked');
    }

    var $checkboxes = $('.each-genre input[type="checkbox"]');
    var countCheckedCheckboxes = $checkboxes.filter(':checked').length;
    if (countCheckedCheckboxes > 0) {
        $("#getstarted-step-2-btn").removeClass('disabled');
        $("#getstarted-step-2-btn").removeAttr('disabled');
    } else {
        $("#getstarted-step-2-btn").addClass('disabled');
        $("#getstarted-step-2-btn").attr('disabled', 'disabled');
    }
    return false;
}

function getstarted_selectUser(id) {
    if ($(".each-welcome-user-"+id + ' input').prop('checked') !== false ) {
        $(".each-welcome-user-"+id + ' i').fadeOut();
        $(".each-welcome-user-"+id + ' input').removeAttr('checked');
    } else {
        $(".each-welcome-user-"+id + ' i').fadeIn();
        $(".each-welcome-user-"+id + ' input').prop('checked', 'checked');
    }

    var $checkboxes = $('.each-welcome-user input[type="checkbox"]');
    var countCheckedCheckboxes = $checkboxes.filter(':checked').length;
    var width = (countCheckedCheckboxes * 10) + 50;
    $('.progress-bar').css('width', width+ '%');
    if (countCheckedCheckboxes > 0) {
        $("#getstarted-step-3-btn").removeClass('disabled');
        $("#getstarted-step-3-btn").removeAttr('disabled');
    } else {
        $("#getstarted-step-3-btn").addClass('disabled');
        $("#getstarted-step-3-btn").attr('disabled', 'disabled');
    }
    return false;
}

function reloadCharts(url) {
    var genre = $("#filter-genre").val();
    var time = $("#filter-time").val();
    load_page(buildLink(url, [{key: 'genre', value: genre}, {key: 'time', value: time}]));
}
function showReportTrack(id){
    $("#reportTrack #report-track").val(id);
    $("#reportTrack").modal("show");
    return false;
}

function showEditPlaylistModal(id) {
    pageLoader();
    $.ajax({
        url : buildLink('load/playlist/form', [{key: 'id' , value: id}]),
        success : function(r) {
            $("#editPlaylistModal .modal-body").html(r);
            pageLoaded();
            $("#editPlaylistModal").modal("show")
        }
    });

    return false;
}
function changeAuthModal(form) {

    $('.login-form').hide();
    $('.signup-form').hide();
    $('.forgot-form').hide();
    if (form == '.signup-form') {
        $('.auth-login-title').removeClass('active');
        $('.auth-signup-title').addClass('active');
    } else {
        $('.auth-signup-title').removeClass('active');
        $('.auth-login-title').addClass('active');
    }
    $(form).show();
    $("#authModal").modal("show");
    return false;
}

window.playlistTrackId = null;
function changePlaylistModal(form, id) {
    if (id === undefined)  id = window.playlistTrackId;
    window.playlistTrackId = id;
    $('.playlists').hide();
    $('.add-playlist').hide();
    if (form == '.add-playlist') {
        $("#add-playlist-id").val(id);
        $('.playlist-title').removeClass('active');
        $('.add-playlist-title').addClass('active');
    } else {
        //load the user playlists
        $.ajax({
            url : buildLink('playlist/load', [{key:'track',value:id}]),
            success : function(c) {
                $('.playlists').html(c);
            }
        });
        $('.add-playlist-title').removeClass('active');
        $('.playlist-title').addClass('active');
    }
    $(form).show();
    $("#playlistModal").modal("show");
    return false;
}

function finishPlaylistCreate() {
    changePlaylistModal('.playlists');
    $('.add-playlist-title input').val('');
}
function finishPlaylistAdd() {
    changePlaylistModal('.playlists');
}
function finishLaterAdd(r) {
    var json = jQuery.parseJSON(r);
    $('.each-track-'+json.track+' .track-later span').html(json.title);
    if (!json.action) {
        if (window.location.href.match('listen-later')) {
            $('.each-track-'+json.track).fadeOut();
        }
    }
}

function followFinished(r) {
    var result = r;
    var btn = $(".follow-"+result.id+'-btn');
    $('.followers-count-'+result.id).html(result.count);
    if (result.action) {
        btn.removeClass('follow');
        btn.html(result.text);
    } else {
        btn.addClass('follow');
        btn.html(result.text);
    }
}
function commentAdded(r) {
    var result = jQuery.parseJSON(r);
    var container = $('.comments-'+result.type+'-'+result.id);

    var r = $("<div style='display: none;'></div>");
    r.html(result.comment);
    container.find('.comments-container-'+result.type+'-'+result.id).prepend(r);
    r.fadeIn();
    container.find('.comments-form-'+result.type+'-'+result.id).find('textarea').val('');

    if ($('.comment-count-'+result.type+'-'+result.id).length > 0) {
        $('.comment-count-'+result.type+'-'+result.id).find('span').html(result.count);
        $('.comment-count-'+result.type+'-'+result.id).fadeIn();
    }
    setTimeout(function() {
        reloadInit();
    }, 300)
    //done
}

function commentAddedAt(trackId) {
    var c = $(".track-time-comment-container-"+trackId);

    c.find('.reply-to').hide();
    c.find('.reply-to input').val('');
    c.find('.at-value').val('');
    c.find('.input input').val('');
    loadTrackTimeComments(trackId);
    c.find('.clicker').hide();
    c.find('form').slideUp();
}

function commentReported(id) {
    $("#comment-"+id).fadeOut();
}
function itemLiked(r) {
    var result = jQuery.parseJSON(r);
    var id = ".like-button-"+result.type+'-'+result.typeId;
    $(id).find('span').html(result.text);
    if (result.action) {
        $(id).addClass('liked');
    } else {
        $(id).removeClass('liked');
    }
    $(".like-count-"+result.type+'-'+result.typeId+ ' span').html(result.count);
    $(".like-count-"+result.type+'-'+result.typeId).fadeIn();
    if (result.count < 1) $(".like-count-"+result.type+'-'+result.typeId).hide();
    if ($("."+result.type+'-likes-'+result.typeId).length > 0) {
        $("."+result.type+'-likes-'+result.typeId).html(result.likes);
    }
}

function itemReposted(r) {
    var result = jQuery.parseJSON(r);
    var type = result.playlist === '0' ? 'track' : 'playlist';
    var typeId = result.playlist === '0' ? result.track : result.playlist;
    var id = ".repost-button-"+type+'-'+typeId;
    $(id).find('span').html(result.text);
    if (result.action) {
        $(id).addClass('reposted');
    } else {
        $(id).removeClass('reposted');
    }
    $(".repost-count-"+type+'-'+typeId+ ' span').html(result.count);
    $(".repost-count-"+type+'-'+typeId).fadeIn();
    if (result.count < 1) $(".repost-count-"+type+'-'+typeId).hide();
}
function commentDeleted(r) {
    var result = jQuery.parseJSON(r);
    $('#comment-'+result.id).fadeOut();
    if ($('.comment-count-'+result.type+'-'+result.id).length > 0) {
        $('.comment-count-'+result.type+'-'+result.id).find('span').html(result.count);
        $('.comment-count-'+result.type+'-'+result.id).fadeIn();
    }
    //Job done
}
function toggleView(t, c) {
    if (c === '' || c === undefined) {
        c = $(t).data('container')
    }
    if ($(t).prop('checked') === true) {
        $(c).fadeIn();
    } else {
        $(c).fadeOut();
    }
}

function toggleDivView(t, c) {
    if (c === '' || c === undefined) {
        c = $(t).data('container')
    }
    if ($(c).css('display') === 'none') {
        $(c).fadeIn();
    } else {
        $(c).fadeOut();
    }
}

function hideView(c, t) {
    if (c === '') {
        c = $(t).data('container')
    }
    $(c).fadeOut();
}



function confirm(url, mess, ajax) {
    iziToast.show({
        theme: 'dark',
        icon: 'icon-person',
        title: '',
        overlay : true,
        zindex : 9999999,
        message: (mess === undefined) ? strings.are_your_sure : mess,
        position: 'center', // bottomRight, bottomLeft, topRight, topLeft, topCenter, bottomCenter
        progressBarColor: 'rgb(0, 255, 184)',
        buttons: [
            ['<button>'+strings.ok+'</button>', function (instance, toast) {
                if (ajax === undefined || !ajax) {
                    window.location.href = url;
                    instance.hide({
                        transitionOut: 'fadeOutUp',
                        onClosing: function(instance, toast, closedBy){
                            console.info('closedBy: ' + closedBy); // The return will be: 'closedBy: buttonName'
                        }
                    }, toast, 'buttonName');
                } else {
                    instance.hide({
                        transitionOut: 'fadeOutUp'
                    }, toast, 'buttonName');
                    ajaxAction(url);
                }
            }, true], // true to focus
            ['<button>'+strings.close+'</button>', function (instance, toast) {
                instance.hide({
                    transitionOut: 'fadeOutUp',
                    onClosing: function(instance, toast, closedBy){
                        console.info('closedBy: ' + closedBy); // The return will be: 'closedBy: buttonName'
                    }
                }, toast, 'buttonName');
            }]
        ]
    });

    return false;
}

function showFormLoader(f) {
    var l = Ladda.create( document.querySelector(f) );
    l.start();
}
function hideFormLoader(f) {
    var l = Ladda.create( document.querySelector(f) );
    l.stop();
}

function notify(m, type) {
    if (type === 'error') {
        iziToast.error({
            message: m,
            position: 'topRight'
        });
    } else if(type === 'success') {
        iziToast.success({
            message: m,
            position: 'topRight'
        });
    } else {
        iziToast.info({
            message: m,
            position: 'topRight'
        });
    }
}

function validate_fileupload(fileName, type)
{
    var allowed_extensions = new Array("jpg","png","gif");
    allowed_extensions = supportImagesType.split(',');
    if (type == 'audio') allowed_extensions = supportAudioType.split(',');
    if (type == 'video') allowed_extensions = supportVideoType.split(',');
    if (type == 'lyric-file') {
        allowed_extensions = new Array("lrc");

    }
    var file_extension = fileName.split('.').pop().toLowerCase(); // split function will split the filename by dot(.), and pop function will pop the last element from the array which will give you the extension as well. If there will be no extension then it will return the filename.
    //alert(file_extension)
    for(var i = 0; i <= allowed_extensions.length; i++)
    {
        if(allowed_extensions[i]==file_extension)
        {
            return true; // valid file extension
        }
    }

    return false;
}

window.trackfiles = [];
function validate_file_size(input, type) {
    var files = input.files;
    if (type === 'audio') {
        if ($(input).hasClass('main-audio')) {
            //window.trackProcessed = 0;
            //window.trackProcessing = false;
        }
    }

    for(ii = 0;ii < files.length;ii++) {
        var file = files[ii];

        if (type == 'image') {
            if (!validate_fileupload(file.name, 'image')) {
                notify(strings.notImageError, 'error')
                $(input).val('');//yes
                return true;
            }

            if (file.size > allowPhotoSize) {
                //this file is more than allow photo file
                notify(strings.allowImageSizeError, 'error');
                //empty the input
                $(input).val('');//yes
                return true;
            }


            if ($(input).data('placeholder') !== undefined) {
                var fr = new FileReader();
                fr.onload = function(){
                    $($(input).data('placeholder')).css('background-image', 'url('+fr.result+')');
                    $($(input).data('placeholder')).fadeIn();
                }
                fr.readAsDataURL(file);

            }
        } else if (type == 'lyric-file') {
            if (!validate_fileupload(file.name, 'lyric-file')) {
                notify(strings.notLyricError, 'error')
                $(input).val('');//yes
                return true;
            }
        }
        else if(type === 'video') {
            if (!validate_fileupload(file.name, 'video')) {
                notify(strings.notVideoError, 'error')
                $(input).val('');//yes
                return true;
            }

            if (file.size > allowVideoSize) {
                //this file is more than allow photo file
                notify(strings.allowVideoSizeError, 'error');
                //empty the input
                $(input).val('');//yes
                return true;
            }

            if ($(input).hasClass('main-video')) {
                $('.video-selector').hide();
                $('#video-form-container').fadeIn();
                var newFileName = file.name.replace(/C:\\fakepath\\/i, '').replace(/.mp4/i, '').replace(/.mov/i, '')
                    .replace(/.wmv/i, '').replace(/.3gp/i, '').replace(/.avi/i, '').replace(/.flv/i, '')
                    .replace(/.f4v/i, '').replace(/.webm/i, '');

                $('.video-title').val(newFileName);
            }

        } else if(type === 'audio') {
            var i = $('.selected-track').length;
            //alert(i);
            if (!validate_fileupload(file.name, 'audio')) {
                notify(strings.notAudioError, 'error')
                $(input).val('');//yes
                return true;
            }

            if (file.size > allowAudioSize) {
                //this file is more than allow photo file
                notify(strings.allowAudioSizeError, 'error');
                //empty the input
                $(input).val('');//yes
                return true;
            }

            newFileName = file.name.replace(/C:\\fakepath\\/i, '').replace(/.mp3/i, '').replace(/.m4a/i, '').replace(/.mp4/i, '');

            if ($(input).hasClass('main-audio')) {
                window.trackfiles[i] = {file: file};
                $("#song--upload-form").data('not-ready', true);
                if ($(input).hasClass('start-main-audio')) {
                    $('.uploader-form').hide();
                    $('.uploader-main-form').fadeIn();
                }
                var itsEdit = false;
                if ($(input).hasClass('edit-audio-file')) {
                    itsEdit = true;
                }

                var addPrice = "";

                var data = $("<div class=' each-upload-track each-upload-track-"+i+"'></div>");
                data.append('<div class="progress-wrapper the-progress-wrapper-'+i+'">' +
                    '                                                <div class="progress the-progress-'+i+'" style="height:5px;" data-id="'+i+'">' +
                    '                                                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>' +
                    '                                                </div> <div class="its-loader"><img src="'+loaderImage+'"/> <span><i style="" class="la la-check"></i></span> </div>' +
                    '                                            </div>');

                data.append("<input class='track-file track-file-"+i+"' data-id='"+i+"' required type='hidden' name='val[trackfiles][]'/>");
                data.append("<input id='title-wave-"+i+"' class='title-wave title-wave-"+i+"' data-id='"+i+"' type='hidden' name='val[titlewaves][]'/>");
                data.append("<input id='title-wave2-"+i+"' type='hidden' data-id='"+i+"'  class='title-wave2 title-wave2-"+i+"' name='val[titlewaves2][]'/>");
                data.append("<input type='hidden' data-id='"+i+"'  class='track-duration' name='val[durations][]'/>");
                data.append("<input type='hidden' data-id='"+i+"'  class='track-size' name='val[sizes][]'/>");
                if (!itsEdit) data.append("<input type='hidden' data-id='"+i+"'  class='avatar-data avatar-data-"+i+"' name='val[avatardata][]'/>");
                if ($(".upload-pricing").length > 0 && !itsEdit) {
                    //$(".upload-pricing").fadeIn();
                    $('#upload-store-info').hide();
                    addPrice = "  <a  style='background: #fff !important;border-color: #F1F1F1 !important;color: #000;' class='btn btn-sm round-sm btn-outline-secondary' onclick='return toggle_price_pane(this)' href=''>"+strings.add_price+"</a>";

                    var pricingTemp = $($('.upload-pricing-temp').html());
                    pricingTemp.find('.demo_wave').attr('name', 'demowave[]');
                    pricingTemp.find('.demo_wave_colored').attr('name', 'demowavecolored[]');
                    pricingTemp.find('input[type=number]').attr('name', 'val[prices][]');


                    if ($(".upload-demo-file").length > 0) {
                        var waterMark = $("<div class='water-mark-input-container'>" +
                            "<label>"+strings.add_watermark_version+"</label>" +
                            "<input type='file' onchange=\"return validate_file_size(this, 'audio')\" data-container-id='"+i+"' class='demo-audio form-control'/>" +
                            "<input type='hidden' class='demo-file demo-file-"+i+"' name='val[demofiles][]'/>" +
                            "<input type='hidden' data-id='"+i+"' class='demo-wave demo-wave-"+i+"' name='val[demowave][]'/>" +
                            "<input type='hidden' data-id='"+i+"' class='demo-wave2 demo-wave2-"+i+"' name='val[demowave2][]'/>" +
                            "<input type='hidden' class='demo-duration demo-duration-"+i+"' name='val[demoduration][]'/>" +
                            "<button class='btn btn-danger btn-sm hide'>"+strings.remove_watermark_version+"</button>" +
                            "</div>")
                        pricingTemp.find('.content').append(waterMark);

                    } else {
                        var preview = $("<div class='water-mark-input-container'>" +
                            "<label>"+strings.add_preview_version+"</label>" +
                            "<input type='file' onchange=\"return validate_file_size(this, 'audio')\" data-container-id='"+i+"' class='demo-audio form-control'/>" +
                            "<input type='hidden' class='demo-file demo-file-"+i+"' name='val[demofiles][]'/>" +
                            "<input type='hidden' data-id='"+i+"' class='demo-wave demo-wave-"+i+"' name='val[demowave][]'/>" +
                            "<input type='hidden' data-id='"+i+"' class='demo-wave2 demo-wave2-"+i+"' name='val[demowave2][]'/>" +
                            "<input type='hidden' class='demo-duration demo-duration-"+i+"' name='val[demoduration][]'/>" +
                            "<button class='btn btn-danger btn-sm hide'>"+strings.remove_preview_version+"</button>" +
                            "</div>")
                        pricingTemp.find('.content').append(preview);
                    }
                }

                var featuringBtn,lyricsBtn,featuringDiv,lyricsDiv = "";
                if (enableFeaturing && !itsEdit) {
                    featuringBtn = "<a href='' style='background: #fff !important;border-color: #F1F1F1 !important;color: #000;' class='btn btn-sm round-sm  btn-outline-secondary' onclick='return toggle_featuring_field(this)'>"+strings.featuring+"</a>";
                    featuringDiv = "<div class='featuring-container hide' style='padding:15px 0'>" +
                        "<div class='clearfix the-custom-url'>" +
                        "<input type='text' placeholder='"+strings.featuring+"' name='val[featuring][]' class='form-control' />" +
                        "<small><strong>("+strings.featuring_note+")</strong></small>" +
                        "</div>" +
                        "</div>";

                }

                if (enableLyrics && !itsEdit) {
                    lyricsBtn = "<a href='' style='background: #fff !important;border-color: #F1F1F1 !important;color: #000;' class='btn btn-sm round-sm  btn-outline-secondary' onclick='return toggle_lyrics_field(this)'>"+strings.add_lyrics+"</a>";
                    lyricsDiv = $("<div class='lyrics-input-container hide' style='padding:15px 0'>" +
                        "<div ><label>"+strings.add_lyrics+"</label>" +
                        "<input type='file' name='lyrics_"+i+"' onchange=\"return validate_file_size(this, 'lyric-file')\" data-container-id='"+i+"' class=' form-control'/>" +
                        "<small><strong>("+strings.add_lyrics_note+"</strong> <a target='_blank' href='https://en.wikipedia.org/wiki/LRC_(file_format)'>en.wikipedia.org/wiki/LRC_(file_format)</a> )</small>" +
                        "</div></div>");
                }

                if (itsEdit) {
                    data.append("<div class='middle clearfix' style=''>" +
                        "<input style='display: none' disabled class='form-control selected-track' type='text' name='val[titles][]' autocomplete='off' required value=\""+newFileName+"\"/>" +
                        "<button class='btn ' style='position:absolute;top: 0; left: 0;height:47px;' onclick='return remove_selected_track(this)'><i class='fa  fa-close'></i></button>" +
                        "</div>" +
                        "");
                } else {

                    data.append("<div class='middle clearfix' style='margin: 0 10px'>" +
                        "<span class='handle'><i class='la  la-align-justify'></i></span>" +
                        "<input class='form-control selected-track' type='text' name='val[titles][]' autocomplete='off' required value=\""+newFileName+"\"/>" +
                        "<button class='btn ' onclick='return remove_selected_track(this)'><i class='fa  fa-close'></i></button>" +
                        "<button class='btn ' style='margin-left:3px' onclick='return toggleDivView(this)' data-container='.all-tab-content-"+i+"'><i class='la  la-pencil'></i></button>" +
                        "</div>" +
                        "");
                    var tabs = $($('.each-track-form-tabs').html());
                    tabs.find('.general-tab').attr('href', '#general-' + i);
                    tabs.find('.metadata-tab').attr('href', '#metadata-' + i);
                    tabs.find('.permission-tab').attr('href', '#permissions-' + i);
                    var d = (i === 0) ? 'block' : 'block'
                    var allTabContent = $("<div style='display:"+d+"' class='all-tab-content-"+i+"'></div>");
                    allTabContent.append(tabs);
                    var tabsContent = $($(".each-track-form-template").html());


                    tabsContent.find('#general').attr('id', 'general-' + i);
                    tabsContent.find('#metadata').attr('id', 'metadata-' + i);
                    tabsContent.find('#permissions').attr('id', 'permissions-' + i);
                    tabsContent.find('.songAvatar').attr('id', 'songAvatar-' + i);
                    tabsContent.find('.songAvatar-input').attr('data-placeholder', '#songAvatar-' + i);
                    tabsContent.find('.songAvatar-input').attr('name', 'img_' + i);
                    allTabContent.append(tabsContent);
                    data.append(allTabContent);
                    initScriptFor(tabsContent, i);
                }

                if (!itsEdit) {
                    var fakeUrl = buildLink('track/')
                    if ($(".upload-pricing").length > 0) {

                        tabsContent.find('#general-' + i).prepend(pricingTemp);
                    }
                    tabsContent.find('#general-' + i).prepend(lyricsDiv);
                    tabsContent.find('#general-' + i).prepend(featuringDiv);
                    tabsContent.find('#general-' + i).prepend("<div class='custom-url-container hide' style='padding:15px 0'>" +
                        "<div class='clearfix the-custom-url' >" +
                        "<input type='text' placeholder='"+strings.custom_url+"' name='val[customurl][]' class='form-control' />" +
                        "<small>"+fakeUrl+"xxx-xxx-xxx <strong>("+strings.alphanum_only+")</strong></small>" +
                        "</div>" +
                        "</div>");

                    if (window.uploadPostType === 'album' && $('#album-price-input').val() !== '') {
                        tabsContent.find('.each-track-price').attr('required', 'required');
                        tabsContent.find('.each-track-price').val(0.9)
                        tabsContent.find('.the-price-container').fadeIn();
                    }

                }
                tabsContent.find('#general-' + i).prepend("" +
                    "<div style='padding:5px 0'><small class='ml-1'><a style='background: #fff !important;border-color: #F1F1F1 !important;color: #000;' href='' class='btn btn-sm round-sm  btn-outline-secondary' onclick='return toggle_custom_url_field(this)'>"+strings.custom_url+"</a> "+addPrice+" "+featuringBtn+" "+lyricsBtn+" </small></div>");
                if (!itsEdit) {
                    $("#selected-songs").append(data);
                } else {
                    $("#selected-songs").html(data);
                }


                //lets submit to server
                var f = new FormData();
                f.append("number", i);
                f.append("track_file", file);
                var ajax = new XMLHttpRequest();
                ajax.upload.uniquedata = i;
                ajax.upload.addEventListener("progress", function(event)  {
                    var position = event.target.uniquedata;
                    var progress = $(".the-progress-" +position);
                    var percent = ((event.loaded / event.total) * 100).toFixed(0);
                    progress.find('.progress-bar').css('width', percent + '%');
                    progress.show();

                }, false);
                ajax.addEventListener("load", function(event) {
                    // Parse the response
                    try {
                        var response = JSON.parse(event.target.responseText);
                        var container = $(".each-upload-track-"+response.number);
                        container.find('.track-file').val(response.audio);
                        container.find('.track-duration').val(response.duration);
                        container.find('.track-size').val(response.size);
                        var position = response.number;

                        if (waveGenerator === 'server') {
                            container.find('.title-wave').val(response.wave);
                            container.find('.title-wave').data('loaded', true);
                            container.find('.title-wave2').val(response.waveColored);
                            container.find('.title-wave2').data('loaded', true);
                            $("#song--upload-form").data('not-ready', false);

                        }
                        hideItsProgress(position);

                    } catch(error) {
                        var response = false;
                    }

                    return true;
                }, false);
                ajax.addEventListener("error", errorHandler, false);
                ajax.addEventListener("abort", abortHandler, false);
                ajax.open("POST", buildLink("upload/track"));
                ajax.send(f);




            }

            if ($(input).hasClass('demo-audio')) {
                $("#song--upload-form").data('not-ready', true);
                i = $(input).data('container-id');

                var f = new FormData();
                f.append("number", i);
                f.append("track_file", file);
                var ajax = new XMLHttpRequest();
                ajax.upload.uniquedata = i;
                ajax.upload.addEventListener("progress", function(event)  {
                    var position = event.target.uniquedata;
                    var progress = $(".the-progress-" +position);
                    var percent = ((event.loaded / event.total) * 100).toFixed(0);
                    progress.find('.progress-bar').css('width', percent + '%');
                    progress.show();

                }, false);
                ajax.addEventListener("load", function(event) {
                    // Parse the response
                    try {
                        var response = JSON.parse(event.target.responseText);
                        var container = $(".each-upload-track-"+response.number);
                        container.find('.demo-file').val(response.audio);
                        container.find('.demo-duration').val(response.duration);
                        container.find('.water-mark-input-container .demo-audio').val('');
                        container.find('.water-mark-input-container .demo-audio').hide();
                        container.find('.water-mark-input-container label').hide();
                        container.find('.water-mark-input-container button').fadeIn();
                        container.find('.water-mark-input-container button').click(function() {
                            container.find('.water-mark-input-container button').hide();
                            container.find('.water-mark-input-container .demo-audio').fadeIn();
                            container.find('.water-mark-input-container label').fadeIn();
                            return false;
                        });
                        var position = response.number;
                        if (waveGenerator === 'server') {
                            container.find('.demo-wave').val(response.wave);
                            container.find('.demo-wave').data('loaded', true);
                            container.find('.demo-wave2').val(response.waveColored);
                            container.find('.demo-wave2').data('loaded', true);
                            $("#song--upload-form").data('not-ready',false);
                        }
                        hideItsProgress(position);

                    } catch(error) {
                        var response = false;
                    }

                    return true;
                }, false);
                ajax.addEventListener("error", errorHandler, false);
                ajax.addEventListener("abort", abortHandler, false);
                ajax.open("POST", buildLink("upload/track"));
                ajax.send(f);

                var dcontainer = $(".each-upload-track-"+i);
                finalizeGenerateWave(files,file, i,dcontainer.find('.demo-wave'),dcontainer.find('.demo-wave2'), true);
            }



        }
    }
    if (type === 'audio' && $(input).hasClass('main-audio')) {
        //$(input).val('');
        generateWaveForm(files);
    }

}


function errorHandler(event) {
    console.log(event);
}
function abortHandler(event) {
    console.log(event);
}

function toggle_custom_url_field(t) {
    var parent = $(t).parent().parent().parent();
    var container = parent.find('.custom-url-container');
    if (container.css('display') == 'none') {
        container.fadeIn();
        container.find('input').focus();
    } else {
        container.hide();
    }
    return false;
}

function toggle_featuring_field(t) {
    var parent = $(t).parent().parent().parent();
    var container = parent.find('.featuring-container');
    if (container.css('display') == 'none') {
        container.fadeIn();
        container.find('input').focus();
    } else {
        container.hide();
    }
    return false;
}

function toggle_lyrics_field(t) {
    var parent = $(t).parent().parent().parent();
    var container = parent.find('.lyrics-input-container');
    if (container.css('display') == 'none') {
        container.fadeIn();

    } else {
        container.hide();
    }
    return false;
}
function toggle_price_pane(t) {
    var parent = $(t).parent().parent().parent();
    var container = parent.find('.the-price-container');
    if (container.css('display') == 'none') {
        container.fadeIn();
        container.find('input').focus();
    } else {
        container.hide();
    }
    return false;
}

function remove_selected_track(t) {
    var parent = $(t).parent().parent();
    parent.remove();
    //count the remain selected tracks if less than 1 show the choose file step
    var count = $('.selected-track').length;
    if (count < 1) {
        $('.uploader-main-form').hide();
        $('.uploader-form').fadeIn();
    }
    return false;
}
function process_upload_playlisttype(t) {

    if ($(".upload-pricing").length > 0 && $(t).val() === '0') {
        //alert($(t).val());
        $('.upload-album-price').fadeIn();
    }
}

function getTrackPicture(tags) {
    var image = tags.tags.picture;
    if (image) {
        var base64String = "";
        for (var i = 0; i < image.data.length; i++) {
            base64String += String.fromCharCode(image.data[i]);
        }
        var base64 = "data:image/jpeg;base64," +
            window.btoa(base64String);
        return base64;

    } else {
        return '';
    }
}

window.trackProcessing = false;
window.trackProcessed = 0;
function generateWaveForm(files, itsEdit) {
    $('.title-wave').each(function() {
        if((!window.trackProcessing && $(this).val() === '') || waveGenerator === 'server') {
            window.trackProcessing = true;
            var i = $(this).data('id');
            var nextWave = $(this).next();
            var thisWave = $(this);
            var avatarData = $(".avatar-data-"+i);
            var file = window.trackfiles[i].file;
            jsmediatags.read(file, {
                onSuccess: function(tag) {
                    picture = getTrackPicture(tag);
                    //console.log(picture);
                    // console.log('calling');
                    if (itsEdit === undefined || !itsEdit) {
                        avatarData.val(picture);
                        upload_song_avatar(i, picture);
                        //alert('am here');
                        if (i === 0 && $('#songAvatar-input').val() === '') {
                            $("#songAvatar").css('background-image', 'url('+picture+')');
                            $("#songAvatar").fadeIn();
                        }
                        $("#songAvatar-" + i).css('background-image', 'url('+picture+')');
                        $("#songAvatar-" + i).fadeIn();
                    }

                    //console.log('jsmediatags Finised');
                    finalizeGenerateWave(files, file, i,nextWave,thisWave);

                },
                onError: function(error) {
                    //console.log(':(', error.type, error.info);
                    //we cannot read
                    finalizeGenerateWave(files,file, i,nextWave,thisWave);
                }
            });
        }
    });
}

function hideItsProgress(i) {
    var canHide = true;

    if ($(".title-wave-"+i).val() === "") return false;
    if ($(".title-wave2-"+i).val() === "") return false;

    if ($(".track-file-"+i).val() === "") return false;
    if (canHide) {
        var progress = $(".the-progress-wrapper-" +i);
        progress.find('.its-loader').addClass('finished-progress-wrapper');
    }

}
function finalizeGenerateWave(files,file, i,nextWave,thisWave, isDemo) {
    if (waveGenerator === 'server') {
        //hideItsProgress(i
        window.trackProcessing = false;
        if( isDemo === undefined) {
            //generateWaveForm(files);
            if (i === $('.title-wave').length - 1) {
                $("#song--upload-form").data('not-ready', false);
                $('.main-audio').val('');
            }
        } else {
            $("#song--upload-form").data('not-ready', false);
        }
        return false;
    }
    SoundCloudWaveform.generate(file, {
        canvas_width: 600,
        canvas_height: 45,
        bar_gap : 0.4,
        bar_width: 4,
        wave_color : ''+settingswaveColor+'',
        onComplete: function(png, pixels) {

            thisWave.val(png);
            upload_wave_png(thisWave, png, 1, isDemo, i);
            window.trackProcessing = false;

            if( isDemo === undefined) {
                generateWaveForm(files);

                if (i === $('.title-wave').length - 1) {
                    $("#song--upload-form").data('not-ready', false);
                    $('.main-audio').val('');
                }
            } else {
                $("#song--upload-form").data('not-ready', false);
            }
        }
    });
}

function upload_wave_png(wave, png,type, iDemo, i) {
    var f = new FormData();
    //console.log('called');
    var demo = (iDemo === undefined) ? 0 : 1;
    $("#song--upload-form").data('not-ready', true);
    f.append("number", wave.data('id'));
    f.append("wave", png);
    f.append("type", type);
    f.append('demo', demo);
    var ajax = new XMLHttpRequest();
    ajax.upload.uniquedata = wave.data('id') + 'wave';
    ajax.addEventListener("load", function(event) {
        // Parse the response
        try {
            var response = JSON.parse(event.target.responseText);
            var container = $(".each-upload-track-"+response.number);

            if (response.demo === '0') {

                if (response.type === '1') {
                    container.find('.title-wave').val(response.value);
                    container.find('.title-wave').data('loaded', true);
                    container.find('.title-wave2').val(response.value2);
                    container.find('.title-wave2').data('loaded', true);
                    hideItsProgress(i);
                }
            } else {
                if (response.type === '1') {
                    container.find('.demo-wave').val(response.value);
                    container.find('.demo-wave').data('loaded', true);
                    container.find('.demo-wave2').val(response.value2);
                    container.find('.demo-wave2').data('loaded', true);
                    hideItsProgress(i);
                }
            }
        } catch(error) {
            var response = false;
        }

        return true;
    }, false);
    ajax.addEventListener("error", errorHandler, false);
    ajax.addEventListener("abort", abortHandler, false);
    ajax.open("POST", buildLink("upload/track/wave"));
    ajax.send(f);
}

function upload_song_avatar(i, picture) {
    var f = new FormData();
    //console.log('called');
    f.append("number", i);
    f.append("picture", picture);
    var ajax = new XMLHttpRequest();
    ajax.upload.uniquedata = i + 'picture';
    ajax.addEventListener("load", function(event) {
        // Parse the response
        try {
            var response = JSON.parse(event.target.responseText);
            var container = $(".each-upload-track-"+response.number);

            container.find('.avatar-data-' + response.number).val(response.value);
            container.find('.avatar-data-' + response.number).data('loaded', true);
        } catch(error) {
            var response = false;
        }

        return true;
    }, false);
    ajax.addEventListener("error", errorHandler, false);
    ajax.addEventListener("abort", abortHandler, false);
    ajax.open("POST", buildLink("upload/track/picture"));
    ajax.send(f);
}
// Animation on Scroll
function initAnimation() {
    var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    if (isMobile == false) {
        $('*[data-animation]').addClass('animated');
        $('.animated').waypoint(function (down) {
            var elem = $(this);
            var animation = elem.data('animation');
            if (!elem.hasClass('visible')) {
                var animationDelay = elem.data('animation-delay');
                if (animationDelay) {
                    setTimeout(function () {
                        elem.addClass(animation + ' visible');
                    }, animationDelay);
                } else {
                    elem.addClass(animation + ' visible');
                }
            }
        }, {
            offset: $.waypoints('viewportHeight')
            //offset: 'bottom-in-view'
            //offset: '95%'
        });
    }
}

function pageLoader() {
    NProgress.configure({ showSpinner: false });
    NProgress.start();
}
function pageLoaded() {
    NProgress.done();
}

function buildLink(link, param) {
    var result = baseUrl;
    var append = "";
    if (param !== undefined) {
        for(i=0;i<param.length;i++) {
            append += (append.length > 0) ? '&'+param[i].key+'='+param[i].value : param[i].key+'='+param[i].value;
        }
    }
    if (permaLink === 1) {
        result += link;
        if(param !== undefined) {
            result += '?'+append;
        }
    } else {
        result += '?p='+link;
        if(param !== undefined) {
            result += '&'+ append;
        }
    }
    return result;
}

window.autocollapsed = false;
function load_page(url, f) {
    window.onpopstate = function(e) {
        load_page(window.location, true);
    };
    //if (url == window.location && f == undefined) return false;

    pageLoader();
    $.ajax({
        url: url,
        cache: false,
        type: 'GET',
        success: function(data) {
            if(data === 'login') {
                changeAuthModal('.login-form');
                pageLoaded();
            } else {
                try {
                    data = jQuery.parseJSON(data);
                    if (data.type !== undefined) {
                        if (data.type == 'error') {
                            notify(data.message, data.type);
                            pageLoaded();
                            return false;
                        }
                    }
                    $('.videoplayer').css('visibility', 'hidden');
                    var content = data.content;
                    var container = data.container;
                    var title = data.title;
                    var collapsed = data.collapsed;
                    if ($('body').hasClass('side-collapsed')) {
                        //leave it like that
                        if (!collapsed) {
                            if (window.autocollapsed) {
                                $('body').removeClass('side-collapsed')
                            }
                        }
                    } else {
                        if (collapsed) {
                            window.autocollapsed = true;
                            $('body').addClass('side-collapsed')
                        } else {
                            if (window.autocollapsed) {
                                $('body').removeClass('side-collapsed')
                            }
                        }
                    }
                    $(container).html(content);
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                    $("#side-menu").html(data.menu);
                    $("#side-menu li a").removeClass('active');
                    $("#side-menu li #"+data.active_menu+'-menu').addClass('active');
                    setTimeout(function() {
                        validatePlayingSong();//if any going
                    }, 200)
                } catch(e) {
                    //window.open(url, '_self');
                }
                document.title = title;
                window.history.pushState({}, 'New URL:' + url, url);
                $(window).scrollTop(0);
                reloadInit();
                pageLoaded();

                $('body').click();
                if ($('.mejs__overlay-button').length > 0) $('.mejs__overlay-button').click();
            }

        },
        error: function() {
            pageLoaded();
            //login_required();
        }
    });
    return false;
}

function scrollToBottom(container) {
    container.animate({scrollTop : container.prop("scrollHeight")}, 200)
}

function initScriptFor(tabsContent, i) {
    var options = {
        create: true,
        sortField: 'text'
    }
    if ($(this).data('popup') !== undefined) {
        options.plugins = ['dropup'];
    }
    tabsContent.find('.select-input-temp').selectize(options);
    tabsContent.find('.upload-genre-input').each(function() {
        $(this).attr('id', 'upload-genre-input-' + i);
    })

    tabsContent.find('.input-tags-temp').each(function() {
        $(this).attr('id', 'input-tags-temp-' + i);
    })
    //tags
    var data = "search/tags";
    if ($(this).data('url') !== undefined) data = $(this).data('url');
    var options = ['remove_button','drag_drop'];
    if ($(this).data('popup') !== undefined) {
        options = ['remove_button','drag_drop', 'dropup'];
    }
    window.globalSelect = tabsContent.find('.input-tags-temp').selectize({
        plugins: options,
        delimiter: ',',
        persist: false,
        preload: true,
        closeAfterSelect: true,
        create: function(input) {
            return {
                value: input,
                text: input
            }
        },
        load: function(query, callback) {
            if (!query.length) return callback();
            $.ajax({
                url: buildLink(data, [{key: 'term', value: encodeURIComponent(query)}]) ,
                type: 'GET',
                error: function() {
                    callback();
                },
                success: function(res) {
                    res = jQuery.parseJSON(res);
                    callback(res);
                }
            });
        }
    });

    tabsContent.find('.track-datepicker').each(function() {
        $(this).addClass('jquery-datepicker');

    })

    tabsContent.find('.jquery-datepicker').datepicker({
        dateFormat: "dd/mm/yy"
    });
    tabsContent.find('.creative-container').addClass('creative-container-' + i)
    tabsContent.find('.all-right-reserve-input').attr('data-container','.creative-container-' + i)
    tabsContent.find('.all-right-reserve-input').attr('name','val[license_'+i+']')
    tabsContent.find('.creative-commons-input').attr('data-container','.creative-container-' + i);
    tabsContent.find('.creative-commons-input').attr('name','val[license_'+i+']')

    //date
    tabsContent.find('.schedule-input').attr('data-container','.select-date-container-' + i);
    tabsContent.find('.schedule-input').attr('name', 'val[privacy_'+i+']')
    tabsContent.find('.public-input').attr('data-container','.select-date-container-' + i)
    tabsContent.find('.public-input').attr('name', 'val[privacy_'+i+']')
    tabsContent.find('.private-input').attr('data-container','.select-date-container-' + i);
    tabsContent.find('.private-input').attr('name', 'val[privacy_'+i+']')
    tabsContent.find('.select-date-container').addClass('select-date-container-'+i);

    tabsContent.find('.stats-toggle').find('input').attr('id', 'cmn-toggle-stats-'+i);
    tabsContent.find('.stats-toggle').find('label').attr('for', 'cmn-toggle-stats-'+i);

    //embed code
    tabsContent.find('.embed-code-toggle').find('input').attr('id', 'cmn-embed-code-toggle-'+i);
    tabsContent.find('.embed-code-toggle').find('label').attr('for', 'cmn-embed-code-toggle-'+i);

    //premiun toggle
    tabsContent.find('.premium-users-toggle').find('input').attr('id', 'cmn-premium-users-toggle-'+i);
    tabsContent.find('.premium-users-toggle').find('label').attr('for', 'cmn-premium-users-toggle-'+i);

    tabsContent.find('.creative_second-input').attr('name', 'val[creative_second_'+i+']');
    tabsContent.find('.download-input').attr('name', 'val[download_'+i+']')
    tabsContent.find('.embed-input').attr('name', 'val[embed_'+i+']')
    tabsContent.find('.stats-input').attr('name', 'val[stats_'+i+']')

    clearupInputTags();
}
function enableAutoFillUploadData(t) {
    if($(t).prop("checked") == true){
        window.autoFillUploadData = true;
    } else {
        window.autoFillUploadData = false;
    }
}

function clearupInputTags() {
    $('.form-group').each(function() {
        var s  = 0
        $(this).find('.input-tags-temp').each(function() {
            if (s > 1) {
                $(this).remove();
            }
            s++;
        })
    })
}
function autoFillTags(t) {
    if (window.autoFillUploadData == false) return false;
    setTimeout(function() {
        $select = $(t);
        var control = $select[0].selectize;
        var value = control.getValue();

        $('.input-tags-temp').each(function() {
            var select = $('#' + $(this).attr('id'))[0];
            if (select != undefined && select.selectize != undefined) {
                select.selectize.destroy();
            }
        });

        $('.input-tags-temp').val(value);
        var options = {
            create: true,
            sortField: 'text'
        }

        var data = "search/tags";
        if ($(this).data('url') !== undefined) data = $(this).data('url');
        var options = ['remove_button','drag_drop'];
        if ($(this).data('popup') !== undefined) {
            options = ['remove_button','drag_drop', 'dropup'];
        }
        clearupInputTags();
        $('.input-tags-temp').selectize({
            plugins: options,
            delimiter: ',',
            persist: false,
            preload: true,
            closeAfterSelect: true,
            create: function(input) {
                return {
                    value: input,
                    text: input
                }
            },
            load: function(query, callback) {
                if (!query.length) return callback();
                $.ajax({
                    url: buildLink(data, [{key: 'term', value: encodeURIComponent(query)}]) ,
                    type: 'GET',
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        res = jQuery.parseJSON(res);
                        callback(res);
                    }
                });
            }
        });

    }, 300)
}
function autoFillDescription(t) {
    if (window.autoFillUploadData == false) return false;
    $('.upload-description-input').val($(t).val())
}

function autoFillGenre(t) {
    if (window.autoFillUploadData == false) return false;
    setTimeout(function() {
        $select = $(t);
        var control = $select[0].selectize;
        var value = control.getValue();

        $('.upload-genre-input').each(function() {
            var select = $('#' + $(this).attr('id'))[0];
            if (select != undefined) {
                select.selectize.setValue(value, true);
            }
        });

    }, 300)
}
function autoFillPurhcaseLink(t) {
    if (window.autoFillUploadData == false) return false;
    $('.upload-purchase-link').val($(t).val())
}
function autoFillRecordLabel(t) {
    if (window.autoFillUploadData == false) return false;
    $('.upload-record-label').val($(t).val())
}
function autoFillDownload(t) {
    if (window.autoFillUploadData == false) return false;
    if($(t).prop("checked") == true) {
        $('.upload-download-input').prop('checked', 'checked');
        $('.upload-download-input').attr('checked', 'checked');
    } else {
        $('.upload-download-input').removeProp('checked');
        $('.upload-download-input').removeAttr('checked');
    }
}
function autoFillEmbed(t) {
    if (window.autoFillUploadData == false) return false;
    if($(t).prop("checked") == true) {
        $('.upload-embed-input').prop('checked', 'checked');
        $('.upload-embed-input').attr('checked', 'checked');
    } else {
        $('.upload-embed-input').removeProp('checked');
        $('.upload-embed-input').removeAttr('checked');
    }
}

function autoFillComments(t) {
    if (window.autoFillUploadData == false) return false;
    $('.upload-comments-input').val($(t).val())
}
function autoFillStatistics(t) {
    if (window.autoFillUploadData == false) return false;
    if($(t).prop("checked") == true) {
        $('.upload-statistics-input').prop('checked', 'checked');
        $('.upload-statistics-input').attr('checked', 'checked');
    } else {
        $('.upload-statistics-input').removeProp('checked');
        $('.upload-statistics-input').removeAttr('checked');
    }
}
function autoFillReleaseDay(t) {
    if (window.autoFillUploadData == false) return false;
    $('.upload-release-day').val($(t).val())
}
function autoFillReleaseMonth(t) {
    if (window.autoFillUploadData == false) return false;
    $('.upload-release-month').val($(t).val())
}
function autoFillReleaseYear(t) {
    if (window.autoFillUploadData == false) return false;
    $('.upload-release-year').val($(t).val())
}
window.autoFillUploadData = false;
function reloadInit(paginate) {
    window.autoFillUploadData = false;
    initAnimation();
    Hook.fire('reload.init');
    try {
        $(".timeago").timeago();
    } catch(e) {
        //console.log(e);
    }

    try {

        $('.rich-editor').each(function() {

            $('#' + $(this).attr('id') ).summernote({
                placeholder: '',
                tabsize: 2,
                height: ($(this).data('height') !== undefined) ? $(this).data('height') : 500
            });
        })
    } catch (e) {}

    $('.homepage-container a').each(function() {
        $(this).removeAttr('data-ajax');
        $(this).removeProp('data-ajax');
    });
    $('#selected-songs').sortable({
        items: '.each-upload-track',
        containment: 'window',
        appendTo: 'body',
        helper: 'clone',
        handle: ".handle",
        update: function(e, ui) {
        }
    });

    $('#selected-songs-edit').sortable({
        items: '.each-upload-track',
        containment: 'window',
        appendTo: 'body',
        helper: 'clone',
        update: function(e, ui) {
        }
    });
    mejs.players = [];

    $('video').mediaelementplayer({
        pluginPath: 'https://cdnjs.com/libraries/mediaelement-plugins/',
        shimScriptAccess: 'always',
        //stretching: 'none',
        autoplay: true,
        enableAutosize: true,
        features: ['playpause', 'current', 'progress', 'duration', 'speed', 'skipback', 'jumpforward', 'tracks', 'markers', 'volume', 'chromecast', 'contextmenu', 'flash', 'fullscreen'],
        success: function (media) {
            $('.videoplayer').css('visibility', 'visible');
            media.addEventListener('ended', function (e) {


            }, false);


            media.addEventListener('play', function (e) {
                var videoid = $('.videoplayer').data('id');
                //resizeVideo();
                $.ajax({
                    url : buildLink('video/play', [{key: 'id', value: videoid}])
                });
            });

            media.addEventListener('playing', function (e) {

                $('#mep_0').css('height', ($('#mep_0').width() / 1.77176216) + 'px');
                $('video, iframe').css('height', '100%');
            });
        },
    });


    if ($('body').hasClass('side-menu-open')) {
        $('body').removeClass('side-menu-open')
    }


    $('.datepicker').pickadate({
        format : 'd/m/yyyy'
    })

    $('.jquery-datepicker').each(function() {
        $(this).datepicker()
    });
    tippy('[data-toggle="tooltip"]',{
        animation: 'shift-toward',
        arrow: true
    });

    tippy('.mtitle',{
        animation: 'shift-toward',
        arrow: true
    });

    tippy('.mtitle-light',{
        animation: 'shift-toward',
        arrow: true,
        interactive : true,
        theme : 'light',
        allowTitleHTML: true,
    });

    //auto place

    const template = document.querySelector('#popover-default')
    const initialText = (template !== null) ? template.textContent : '';
    const tip = tippy('.user-link', {
        allowTitleHTML: true,
        arrow : true,
        maxWidth: '200px',
        theme: 'light',
        html : '#popover-default',
        interactive : true,
        onShow(instance) {
            var userid = this._reference.getAttribute('data-id');
            const content = this.querySelector('.tippy-content')

            if (tip.loading || content.innerHTML !== initialText) return

            tip.loading = true;

            $.ajax({
                url : buildLink('user/card', [{key: 'id', value: userid}]),
                success : function(r) {
                    content.innerHTML = r;
                    tip.loading = false
                }
            })
        },
        popperOptions: {
            modifiers: {
                preventOverflow: {
                    enabled: false
                },
                hide: {
                    enabled: false
                }
            }
        }
    })

    $('.mtooltip').tooltip({
        trigger: 'hover'
    });
    $('.mtooltip').on('click', function() {
        $(this).tooltip('hide')
    });

    $('.cp').colorpicker();

    $('.jquery-datepicker').each(function() {
        $(this).datepicker()
    });

    if ($('.chats-container').length > 0) {
        if(paginate === undefined) {
            setTimeout(function() {
                scrollToBottom($('.chats-container'))
            }, 100);
        }
        $('.chats-container').scroll(function() {
            if ($(this).scrollTop() == 0 ) {
                globalPaginate('chats-container');
            }
        })
    };

    if($('#charts-stats').length > 0) {
        var time = $('#charts-stats').data('time');
        var id = $('#charts-stats').data('id')
        $.ajax({
            url: buildLink('load/charts', [{key:'time',value:time},{key: 'id', value: id}]),
            success: function(data) {
                var json = jQuery.parseJSON(data);
                $.each(json.charts, function(i, c) {
                    var yD = [];
                    xKey = 'y';
                    yKeys = [];
                    labels = [];
                    //alert(c);

                    $.each(c, function(n, nC) {
                        labels.push(nC.name);
                        yKeys.push(n);
                        var x = 0;
                        $.each(nC.points, function($name, $number) {
                            var o = (yD[x] != undefined) ? yD[x] : {y: $name};
                            if(yD[x] != undefined) {
                                yD[x][n] = $number;
                            } else {
                                o[n] = $number;
                                yD.push(o);
                            }
                            x++;
                        });

                    });
                    //alert(yD);

                    var divId = 'chat-' + i;
                    var div = $("<div id='" + divId + "' style='width: 100%;height: 300px'></div> ");
                    $("#charts-stats ").find('img').hide();
                    $("#charts-stats").append(div);
                    var chartType = $("#charts-stats").data('type');
                    if (chartType === 'bar') {
                        Morris.Bar({
                            element: divId,
                            data: yD,
                            xkey: xKey,
                            ykeys: yKeys,
                            labels: labels,
                            pointSuperimposed: false,
                            dataLabels: false,
                            parseTime: false
                        });
                    } else if(chartType === 'line') {
                        Morris.Line({
                            element: divId,
                            data: yD,
                            xkey: xKey,
                            ykeys: yKeys,
                            labels: labels,
                            pointSuperimposed: false,
                            dataLabels: false,
                            parseTime: false
                        });
                    } else {
                        Morris.Area({
                            element: divId,
                            data: yD,
                            xkey: xKey,
                            ykeys: yKeys,
                            labels: labels,
                            pointSuperimposed: false,
                            dataLabels: false,
                            parseTime: false
                        });
                    }

                });
            }
        })
    }

    $('.track-list').each(function() {
        ///return false;
        if($(this).data('loaded') === undefined) {
            var type = $(this).data('type');
            var typeId= $(this).data('type-id');
            var offset = $(this).data('offset');
            var limit = ($(this).data('limit') === undefined) ? 0 : $(this).data('limit');
            var viewType = ($(this).data('view') === undefined) ? 'list' : $(this).data('view');
            var container = $(this);
            $.ajax({
                url : buildLink('track/load', [{key: 'limit',value:limit},{key: 'type',value:type},{key: 'type_id',value:typeId},{key: 'offset',value:offset},{key:'view',value:viewType}]),
                success : function(result) {
                    container.find('.feed-placeholder').remove();
                    if (container.data('loaded') === undefined) {
                        var r = $("<div style='display: none;'></div>");
                        r.html(result);
                        container.append(r);
                        container.data('loaded', true);
                        r.fadeIn();
                        setTimeout(function() {
                            reloadInit();

                        },300);
                        setTimeout(function() {
                            validatePlayingSong();//if any goin
                            recalculateWaveImages();
                        }, 200)
                    }

                }
            })
        }
    });

    $('.comments-container').each(function() {
        if($(this).data('loaded') === undefined) {
            var type = $(this).data('type');
            var typeId= $(this).data('type-id');
            var offset = $(this).data('offset');
            var container = $(this);
            $.ajax({
                url : buildLink('comment/load', [{key: 'type',value:type},{key: 'type_id',value:typeId},{key: 'offset',value:offset}]),
                success : function(result) {
                    var r = $("<div style='display: none;'></div>");
                    r.html(result);
                    container.append(r);
                    container.data('loaded', true);
                    r.fadeIn();
                    setTimeout(function() {
                        reloadInit();
                    },300)

                }
            })
        }
    });
    prepare_track_time_comments();
    if ($(window).width() > 1000) {
        try {
            $('#page-container .fixed-left-content').stick_in_parent({parent: '#page-container'});
        } catch(e) {
        }
    }

    $('.select-input').each(function() {
        if($(this).data('ui-select') && !$(this).hasClass('selectized')) {
            var options = {
                create: true,
                sortField: 'text',
            }
            if ($(this).data('popup') !== undefined) {
                options.plugins = ['dropup'];
            }
            $(this).selectize(options);
        }
    });

    $('.select-input-multiple').each(function() {
        if($(this).data('ui-select') && !$(this).hasClass('selectized')) {
            var options = {
                create: true,
                sortField: 'text',
                plugins: ['remove_button']
            }
            if ($(this).data('popup') !== undefined) {
                options.plugins = ['dropup','remove_button'];
            }
            $(this).selectize(options);
        }
    });

    if ($('#track-header').length > 0 && $('#track-header').data('bg-set') === false) {

    }

    $('.input-tags').each(function() {
        var data = "search/tags";
        if ($(this).data('url') !== undefined) data = $(this).data('url');
        var options = ['remove_button','drag_drop'];
        if ($(this).data('popup') !== undefined) {
            options = ['remove_button','drag_drop', 'dropup'];
        }
        window.globalSelect = $(this).selectize({
            plugins: options,
            delimiter: ',',
            persist: false,
            preload: true,
            closeAfterSelect: true,
            create: function(input) {
                return {
                    value: input,
                    text: input
                }
            },
            load: function(query, callback) {
                if (!query.length) return callback();
                $.ajax({
                    url: buildLink(data, [{key: 'term', value: encodeURIComponent(query)}]) ,
                    type: 'GET',
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        res = jQuery.parseJSON(res);
                        callback(res);
                    }
                });
            }
        });
    });



    $('.input-tags-fetch').each(function() {
        var data = "search/tags";
        if ($(this).hasClass('selectized')) return false;
        if ($(this).data('url') !== undefined) data = $(this).data('url');

        var options = ['remove_button','drag_drop'];
        if ($(this).data('popup') !== undefined) {
            options = ['remove_button','drag_drop', 'dropup'];
        }
        $(this).selectize({
            plugins: options,
            delimiter: ',',
            persist: false,
            options: [],
            preload: true,
            closeAfterSelect: true,
            load: function(query, callback) {
                if (!query.length) return callback();
                $.ajax({
                    url: buildLink(data, [{key: 'term', value: encodeURIComponent(query)}]) ,
                    type: 'GET',
                    error: function() {
                        callback();
                    },
                    success: function(res) {
                        res = jQuery.parseJSON(res);
                        callback(res);
                    }
                });
            }
        });
    });



    $('.slider').each(function() {
        var id = $(this).find('.swiper-container').attr('id');
        var next = $(this).find('.scroll-right').attr('id');
        var prev = $(this).find('.scroll-left').attr('id');
        var swiper = new Swiper('#'+id, {
            slidesPerView: 2,
            spaceBetween: 1,
            loop: false,
            speed: 1500,
            navigation: {
                nextEl: '#'+next,
                prevEl: '#'+prev,
            },
            breakpoints: {
                2560: {
                    slidesPerView: 4,
                    spaceBetween: 10,
                },
                1800: {
                    slidesPerView: 4,
                    spaceBetween: 10,
                },
                1400: {
                    slidesPerView: 3,
                    spaceBetween: 10,
                },
                992: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                768: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                640: {
                    slidesPerView: 3,
                    spaceBetween: 15,
                },
                480: {
                    slidesPerView: 1,
                },
                375: {
                    slidesPerView: 1,
                    spaceBetween: 0,
                }
            },
        });
    });

    $('.full-slider').each(function() {
        var id = $(this).find('.swiper-container').attr('id');
        var next = $(this).find('.scroll-right').attr('id');
        var prev = $(this).find('.scroll-left').attr('id');
        var swiper = new Swiper('#'+id, {
            slidesPerView: 2,
            spaceBetween: 5,
            loop: false,
            speed: 1500,
            navigation: {
                nextEl: '#'+next,
                prevEl: '#'+prev,
            },
            breakpoints: {
                4560 : {
                    slidesPerView: 14,
                },
                4200 : {
                    slidesPerView: 13,
                },
                3900 : {
                    slidesPerView: 12,
                },
                3560 : {
                    slidesPerView: 11,
                },
                3200 : {
                    slidesPerView: 10,
                },
                2900 : {
                    slidesPerView: 9,
                },
                2560: {
                    slidesPerView: 8,
                },
                2200 : {
                    slidesPerView: 7,
                },
                1900: {
                    slidesPerView: 5,
                },
                1600 : {
                    slidesPerView: 5,
                },
                1500 : {
                    slidesPerView: 4,
                },
                1400: {
                    slidesPerView: 4,
                },
                992: {
                    slidesPerView: 4,
                    spaceBetween: 10,
                },
                768: {
                    slidesPerView: 3,
                    spaceBetween: 10,
                },
                640: {
                    slidesPerView: 2,
                    spaceBetween: 15,
                },
                480: {
                    slidesPerView: 2,
                },
                375: {
                    slidesPerView: 1,
                    spaceBetween: 0,
                }
            },
        });
    })

    $('.side-slider').each(function() {
        var id = $(this).find('.swiper-container').attr('id');
        var next = $(this).find('.scroll-right').attr('id');
        var prev = $(this).find('.scroll-left').attr('id');
        var swiper = new Swiper('#'+id, {
            slidesPerView: 2,
            spaceBetween: 1,
            loop: false,
            autoplay: true,
            speed: 1500,
            navigation: {
                nextEl: '#'+next,
                prevEl: '#'+prev,
            },
            breakpoints: {
                2560: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                1800: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                1400: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                992: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                768: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                640: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                480: {
                    slidesPerView: 2,
                    spaceBetween: 10,
                },
                375: {
                    slidesPerView: 1,
                    spaceBetween: 10,
                }
            },
        });
    });

    $('.home-slider').each(function() {
        var id = $(this).find('.swiper-container').attr('id');
        var next = $(this).find('.scroll-right').attr('id');
        var prev = $(this).find('.scroll-left').attr('id');
        var swiper = new Swiper('#'+id, {
            slidesPerView: 1,
            spaceBetween: 0,
            loop: true,
            autoplay: true,
            speed: 500,
            navigation: {
                nextEl: '#'+next,
                prevEl: '#'+prev,
            },
        });
    })
    $("#music-player").jPlayer("option", "cssSelectorAncestor", '.sound-player');
}

window.currentPlaying  = 0;
window.currentPlayingType = '';
window.currentPlayingTypeId = '';
window.repeatSong = false;
window.randomSong = false;
window.currentPlayingObject = null;

function loadPlayer(type, typeId) {
    $('.sound-playlist-content').data('offset', 0);
    $('.sound-playlist-content').data('type', type);
    $('.sound-playlist-content').data('typeid', typeId);
    $('.sound-playlist-content').data('limit', 20);
    $('.sound-playlist-content').removeData('ended');
    $('.sound-playlist-content').removeAttr('data-ended')
    $.ajax({
        url : buildLink('track/load', [{key: 'type', value:type},
            {key:'type_id', value:typeId},
            {key:'view', value:'small-inline-nextup'},
            {key: 'offset', value: 0}, {key:'limit',value:20}]),
        success : function(r) {
            $('.sound-playlist-content .content').html(r);

            validatePlayingSong();
            if (window.currentPlaying !== 0) {
                $('.sound-playlist-content .each-inline-track').removeClass('track-hover-display-container');
                $('.sound-playlist-content .each-inline-track').addClass('track-hover-display-container');
                $('.sound-playlist-content').find('.each-track-'+window.currentPlaying).addClass('active-track-mini-inline current-seek');
                $('.sound-playlist-content').find('.each-track-'+window.currentPlaying +" .play-controls").html($("#seek-template").html());
                $('.sound-playlist-content').find('.each-track-'+window.currentPlaying).removeClass('track-hover-display-container');
                $("#music-player").jPlayer("option", "cssSelectorAncestor", '.sound-player');
            }
        }
    })
}
function toogleSoundPlaylist() {
    if ($("#sound-playlists").css('display') === 'none') {
        $("#sound-playlists").fadeIn();
        if ($('.sound-playlist-content .content').html() === '') {
            //lets query the feed list to play from
            loadPlayer('feed', '')
        }
    } else {
        $("#sound-playlists").hide();
    }
    return false;
}
window.playStopPercent = 0;
window.playBuyType = null;
window.playBuyTrack = null;
window.playerLyrics = null;
window.playerTrackDuration = null;
window.sponsoredPlaying = false;
window.beforeSponsoredObj = null;
window.radioListenerInterval = null;
window.radioPlaying = null;
window.radioListenerIntervalGoing = false;
function processRadioListenerInterval() {
    if (window.radioPlaying == null) {
        clearInterval(window.radioListenerInterval);
        return true;
    }
    if (window.radioListenerIntervalGoing) return false;
    window.radioListenerIntervalGoing = true;
    $.ajax({
        url : buildLink('radio/set/views', [{key : 'track', value: window.radioPlaying}]),
        success : function(r) {
            if (r > 0) {
                $('.player-radio-listener span').html(r);
                $('.player-radio-listener').fadeIn();
                $('.radio-listener-' + window.radioPlaying).find('span').html(r);
                $('.radio-listener-' + window.radioPlaying).fadeIn();
            }
            window.radioListenerIntervalGoing = false;
        }
    });
}

var Hook = {
    hooks: [],

    register: function(name, callback) {
        if(typeof Hook.hooks[name] === 'undefined') {
            Hook.hooks[name] = [];
        }
        Hook.hooks[name].push(callback);
    },

    fire: function(name, result, arguments) {
        if(typeof arguments === 'undefined' || (typeof arguments !== 'undefined' && !Array.isArray(arguments))) {
            arguments = [];
        }
        arguments.unshift(result);
        if(typeof Hook.hooks[name] !== 'undefined') {
            for(var i = 0; i < Hook.hooks[name].length; i++) {
                arguments[0] = result;
                result = Hook.hooks[name][i].apply(undefined, arguments);
            }
        }
        return result;
    }
};

function playSong(b, f) {
    var button = $(b);

    window.currentPlayingObject = b;
    trackId = button.data('id');

    window.playerLyrics = null;


    window.showTimeComments = [];
    type = button.data('type');
    typeId = button.data('typeid');
    if (f == undefined) {
        if (!window.repeatSong && (trackId === window.currentPlaying && typeId === window.currentPlayingTypeId)) return false;
    }
    owner = button.data('owner');
    ownerLink = button.data('owner-link');
    art = button.data('art');
    title = button.data('title');
    $('.player-lyrics-container').hide();
    $('.skip-ads-button').hide();
    link = button.data('link');
    Hook.fire('play.song', null, [trackId,type, typeId]);
    if (type === 'radio') {
        window.playingWave = null;
        window.playingWaveColored = null;
        window.radioPlaying = button.data('id');
        window.currentPlaying = button.data('id');
        window.currentPlayingType = type;
        window.currentPlayingTypeId = typeId;

        loadPlayer(type, typeId);
        validatePlayingSong();
        var profileLink = button.data('profileLink');

        if (listenerInterval > 0) {
            if (window.radioListenerInterval != null) clearInterval(window.radioListenerInterval);
            processRadioListenerInterval();
            window.radioListenerInterval = setInterval(function() {
                processRadioListenerInterval();
            },listenerInterval * 1000);
        }

        $('.sound-container').fadeIn();
        $('.sound-container .hide').removeClass('hide');
        $('.sound-container .detail #title-link').html(title);
        $('.sound-container .detail #title-link').attr('href', profileLink);
        $('.sound-container .detail #owner-link').html(owner);
        $('.sound-container .detail #owner-link').attr('href', ownerLink);
        $('.sound-container .detail .player-img-link').attr('href', link);
        $('.sound-container .detail .img').css("background-image", "url("+art+")");
        $("#music-player").jPlayer("option", "cssSelectorAncestor", '.sound-player');
        $("#music-player").jPlayer("setMedia",{mp3:link}).jPlayer("play");
        $(".player-track-buttons").html('');//hide current content to prevent confusion
        $.ajax({
            url :  buildLink('load/radio/player/buttons', [{key: 'radio', value: trackId}]) ,
            success : function(d) {
                $(".player-track-buttons").html(d);
            }
        });
    } else {
        window.radioPlaying = null;
        $.ajax({
            url : buildLink('tr/a/' + trackId + '/details'),
            success : function(r) {
                var json = jQuery.parseJSON(r);
                //console.log(json);
                if (json.sponsored !== undefined) {
                    window.sponsoredPlaying = true;
                    window.beforeSponsoredObj = b;
                }
                trackUrl = json.url;
                link = json.link;
                owner = json.owner;
                ownerLink = json.ownerLink;
                art = json.art;
                title = json.title;
                window.playerTrackDuration = json.duration;
                if (json.lyrics !== '') {
                    window.playerLyrics = Lrc(json.lyrics);
                }
                //console.log(window.playerLyrics);
                if (json.needLogin !== undefined) {
                    if (!isLoggedIn) {
                        changeAuthModal('.login-form');
                        return false;
                    }
                }
                if (json.needPremium !== undefined) {
                    showNeedPremiumModal();
                    return false;
                }
                if (json.limit !== 0) {
                    //@TODO limit playing by certain seconds for default
                    if (trackUrl === '') {
                        //that means the user must by
                        return buy_item(json.buyType, json.buyTrack);
                    } else {
                        if (json.percentage > 0) {
                            window.playStopPercent = json.percentage;
                            window.playBuyTrack = json.buyTrack;
                            window.playBuyType = json.buyType;
                        } else {
                            window.playStopPercent = 0;
                        }
                    }
                } else {
                    window.playStopPercent = 0;
                }
                window.playingWave = json.wave;
                window.playingWaveColored = json.wave_colored;

                window.currentPlaying = trackId;
                window.currentPlayingType = type;
                window.currentPlayingTypeId = typeId;
                loadPlayer(type, typeId);
                validatePlayingSong();

                if (type === 'playlist') {
                    if ($('.playlist-url-'+typeId).length > 0) link = $('.playlist-url-'+typeId).data('link');
                }
                $.ajax({
                    url : buildLink('track/set/views', [{key : 'track', value: trackId}])
                });
                $('.sound-container').fadeIn();
                $('.sound-container .hide').removeClass('hide');
                $('.sound-container .detail #title-link').html(title);
                $('.sound-container .detail #title-link').attr('href', link);
                $('.sound-container .detail #owner-link').html(owner);
                $('.sound-container .detail #owner-link').attr('href', ownerLink);
                $('.sound-container .detail .player-img-link').attr('href', link);
                $('.sound-container .detail .img').css("background-image", "url("+art+")");
                $("#music-player").jPlayer("option", "cssSelectorAncestor", '.sound-player');
                $("#music-player").jPlayer("setMedia",{mp3:trackUrl}).jPlayer("play");
                $(".player-track-buttons").html('');//hide current content to prevent confusion
                $.ajax({
                    url :  buildLink('load/track/player/buttons', [{key: 'track', value: trackId}]) ,
                    success : function(d) {
                        $(".player-track-buttons").html(d);
                    }
                });

                if (json.sponsored !== undefined) {
                    $('.skip-ads-button').show();

                }

                if ($('.playlist-profile').length > 0) {
                    /**Vibrant.from(art).getPalette().then(function(palette) {
                       // $('#track-header .bg-container').css('background', 'linear-gradient(to right, rgb('+palette.DarkVibrant._rgb+') ,rgb('+palette.Muted._rgb+'), rgb('+palette.Vibrant._rgb+')) ')
                    });**/

                    $('.playlist-profile .track-image').css('background-image', 'url('+art+')');
                } else if (window.currentPlayingType === 'playlist' && $(".playlist-list-art-" + window.currentPlayingTypeId).length > 0) {
                    //$(".playlist-list-art-" + window.currentPlayingTypeId + ' .art').css('background-image', 'url('+art+')');
                }
            }
        })
    }

    return false;
}

function skipAds() {
    $('.player-lyrics-container').hide();
    window.sponsoredPlaying = false;
    playSong(window.beforeSponsoredObj, true);
    return false;
}

function validatePlayingSong() {

    if (window.currentPlaying !== 0) {
        var trackId = window.currentPlaying;

        var button = $(".track-play-button-" + window.currentPlaying);
        if (button.length > 0) {
            wave = window.playingWave;

            waveColored = window.playingWaveColored;
            $('.current-seek').find('.jp-audio').remove();
            $('.current-seek').find('.current-seek-wave').remove();
            $('.current-seek').removeClass('current-seek');


            if (window.currentPlayingType === 'playlist') {
                $(".playlist-container-"+window.currentPlayingTypeId).addClass('current-seek');
                $('.current-seek  .play-controls').append($("#seek-template").html());
                $(".playlist-tracks-list-"+window.currentPlayingTypeId+ " .track-"+trackId).addClass("current-seek");
                $(".playlist-tracks-list-"+window.currentPlayingTypeId+ " .track .play-controls").html("");
                $(".playlist-tracks-list-"+window.currentPlayingTypeId+ " .each-track-"+window.currentPlaying+" .play-controls").html($("#seek-template").html());

            } else {
                $(".track-"+trackId).addClass("current-seek");
                $('.current-seek  .play-controls').append($("#seek-template").html());
            }


            var height = $('.track-'+trackId + ' .detail').height() + 5;

            $('.track-'+trackId+' .jp-play , .track-'+trackId+' .jp-pause').css({ 'top' : '-' + height + 'px' });

            //replace the waveform
            // $(".current-seek .jp-progress").css('background-image', 'url('+wave+')');
            if (window.currentPlayingType !== 'radio') {
                $(".current-seek .jp-progress").prepend("<img src='"+wave+"' class='current-seek-wave wave-image'/>");
                $(".current-seek .jp-progress .jp-seek-bar .jp-play-bar").append("<img src='"+waveColored+"' class='current-seek-wave wave-image'/>")
                $("#music-player").jPlayer("option", "cssSelectorAncestor", '.sound-player');
                $('.jp-current-time').show();
                recalculateWaveImages();
            } else {
                $('.track-profile .jp-current-time').hide();
            }
        }

    }
}

function playprev() {
    if (window.currentPlaying !== 0) {
        if (window.randomSong) {
            playSongRandomly();
        } else {
            var prevTrack = $('#sound-playlists .track-' + window.currentPlaying).prev();
            if (prevTrack.hasClass('each-inline-track')) {
                playSong(prevTrack.find('.play-button'))
            }
        }
    }
    return false;
}

function playNext(manual) {
    if (window.isEmbedPlaying) return false;
    if ($('#embed-body-container').length > 0) return false;
    if (window.currentPlaying !== 0) {
        if (manual !== undefined) {
            var r = Hook.fire('play.next', false, []);
            if (r) return false;
        }
        if (window.randomSong) {
            playSongRandomly();
        } else {
            console.log('play next triggered')
            var nextTrack = $('#sound-playlists .track-' + window.currentPlaying).next();
            if (nextTrack.hasClass('each-inline-track')) {
                playSong(nextTrack.find('.play-button'))
            } else {
                paginateSoundPlaylist(true)
            }
        }
    }
    return false;
}
function rand(arg) {
    if ($.isArray(arg)) {
        return arg[rand(arg.length)];
    } else if (typeof arg === "number") {
        return Math.floor(Math.random() * arg);
    } else {
        return 4;  // chosen by fair dice roll
    }
}
function playSongRandomly() {

    var ids = new Array();
    $("#sound-playlists .each-inline-track").each(function() {
        ids.push($(this).attr('id'));
    });

    var item = ids[Math.floor(Math.random()*ids.length)];
    playSong($('#' + item).find('.play-button'));
    paginateSoundPlaylist(); //to have more tracks
}

function repeatSongTrigger(t) {
    if (window.repeatSong === true) {
        window.repeatSong = false;
        $(t).find('i').removeClass('colored')
    } else {
        window.repeatSong = true;
        $(t).find('i').addClass('colored')
    }
    return false;
}
function randomSongTrigger(t) {
    if (window.randomSong === true) {
        window.randomSong = false;
        $(t).find('i').removeClass('colored')
    } else {
        window.randomSong = true;
        $(t).find('i').addClass('colored')
    }
    return false;
}

window.trackPaginating = false;
function paginateTracks() {
    if ($('.track-list').data('loaded') === undefined) return false;
    if ( $('.track-list').data('no-paginate') === undefined && $('.track-list').length > 0 && window.trackPaginating === false && $('.track-list').data('ended') === undefined) {
        window.trackPaginating = true;
        var offset = $('.track-list').data('offset');
        var typeId = $('.track-list').data('type-id');
        var type = $('.track-list').data('type');
        var viewType = ($('.track-list').data('view') === undefined) ? 'list' : $('.track-list').data('view');
        $('.track-list').append('<div class="ms_bars"><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div></div>');
        $.ajax({
            url : buildLink('track/paginate', [
                {key: 'offset', value: offset},
                {key: 'view', value: viewType},
                {key: 'type_id',value:typeId},
                {key:'type',value:type}]),
            success : function(r) {
                window.trackPaginating = false;
                try{
                    var result = jQuery.parseJSON(r);
                    $('.track-list').data('offset', result.offset);
                    $('.track-list').find('.ms_bars').remove();
                    r = $("<div style='display: none;'></div>");
                    r.html(result.content);
                    $('.track-list').append(r);
                    r.fadeIn();
                    if (result.content === '') {
                        $('.track-list').data('ended', true);
                    }
                    setTimeout(function() {
                        reloadInit();
                    },300);
                    setTimeout(function() {
                        validatePlayingSong();//if any goin
                    }, 300);
                } catch(e) {
                    $('.track-list').data('ended', true);
                    $('.track-list').find('.ms_bars').remove();
                }
            }
        })
    }
    return false;
}

function paginateSoundPlaylist(doNext) {
    if (window.trackPaginating === false && $('.sound-playlist-content').data('ended') === undefined) {
        window.trackPaginating = true;
        var offset = $('.sound-playlist-content').data('offset');
        var typeId = $('.sound-playlist-content').data('typeid');
        var type = $('.sound-playlist-content').data('type');
        var viewType = 'small-inline';
        $('.sound-playlist-content .content').append('<div class="ms_bars"><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div></div>');
        $.ajax({
            url : buildLink('track/paginate', [
                {key: 'offset', value: offset},
                {key: 'view', value: viewType},
                {key: 'type_id',value:typeId},
                {key:'type',value:type}, {key:'limit',value:20}]),
            success : function(r) {
                window.trackPaginating = false;
                try{
                    var result = jQuery.parseJSON(r);
                    $('.sound-playlist-content').data('offset', result.offset);
                    $('.sound-playlist-content .content').find('.ms_bars').remove();
                    r = $("<div style='display: none;'></div>");
                    r.html(result.content);
                    $('.sound-playlist-content .content').append(r);
                    r.fadeIn();

                    if (doNext !== undefined && result.content !== '') {
                        playNext();
                    }

                    if (result.content === '') {
                        $('.sound-playlist-content').data('ended', true);
                    }
                    setTimeout(function() {
                        reloadInit();
                    },300);
                    setTimeout(function() {
                        validatePlayingSong();//if any goin
                    }, 300)
                } catch(e) {
                    $('.sound-playlist-content').data('ended', true);
                    $('.sound-playlist-content .content').find('.ms_bars').remove();
                }
            }
        })
    }
}


function ajaxAction(url) {
    pageLoader();
    $.ajax({
        url : url,
        success : function(d) {
            pageLoaded();
            if (d === 'login') {
                changeAuthModal('.login-form');
            }
            try{
                var r = jQuery.parseJSON(d);
                $('body').click()//to remove unwanted dropdowns
                var m = r.message;var t = r.type;var v = r.value;var c = r.content,modal = r.modal,table=r.table;
                if (t === 'url') {
                    load_page(v);
                } else if(t === 'function') {
                    if (v !== undefined && v !== '') eval(v)(c);
                } else if(t === 'modal-function') {
                    $(modal).modal("hide");
                    if (v !== undefined && v !== '') eval(v)(c);
                } else if(t === 'modal-url') {
                    $(c).modal("hide");
                    load_page(v)
                } else if(t === 'reload') {
                    load_page(window.location.href);
                } else if(t === 'reload-modal') {
                    $(c).modal("hide");
                    load_page(window.location.href);
                }
                if (t === 'error') {
                    notify(m, 'error');
                } else {
                    if (m !== '' ) notify(m, 'success');
                }
            } catch (e) {
            }
        }
    })
}

window.globalPaginating = false
function globalPaginate(container) {
    var btnId = '#'+container + '-loadmore-button';
    var loadMoreContainer = '#'+container + '-load-more-container';
    var container = $("#"+container);
    if (container.length > 0 && window.globalPaginating === false && container.data('ended') === undefined) {

        window.globalPaginating = true;
        var offset = container.data('offset');
        var data = container.data('param');
        //alert(buildLink(container.data('paginate-url'), [{key: 'offset', value: offset},{key: 'data',value:data}]))
        showFormLoader(btnId);
        $.ajax({
            type : 'POST',
            data : {data : data},
            url : buildLink(container.data('paginate-url'), [{key: 'offset', value: offset}]),
            success : function(r) {
                window.globalPaginating = false;
                hideFormLoader(btnId);
                //alert(r);
                try{
                    var result = jQuery.parseJSON(r);
                    container.data('offset', result.offset);
                    r = $("<div style='display: none;'></div>");
                    r.html(result.content);
                    if (container.data('prepend') === undefined) {
                        var newLoadMore = $(loadMoreContainer).clone();
                        $(loadMoreContainer).remove();
                        container.append(r);
                        container.append(newLoadMore)
                    } else {

                        container.prepend(r);

                    }
                    r.fadeIn();
                    if (result.content === '') {
                        container.data('ended', true);
                        $(btnId).hide();
                    }
                    setTimeout(function() {
                        reloadInit(true);
                    },300);
                    setTimeout(function() {
                        validatePlayingSong();//if any goin
                    }, 300);
                } catch(e) {
                    container.data('ended', true);
                    $(btnId).hide();
                }
            }
        })
    }
}

function _rn(num, scale) {
    if(!("" + num).includes("e")) {
        return +(Math.round(num + "e+" + scale)  + "e-" + scale);
    } else {
        var arr = ("" + num).split("e");
        var sig = ""
        if(+arr[1] + scale > 0) {
            sig = "+";
        }
        return +(Math.round(+arr[0] + "e" + sig + (+arr[1] + scale)) + "e-" + scale);
    }
}
function Lrc(text) {
    var lines = text.split('\n');
    var result = {};

    lines.forEach(function (line) {
        var timeAnchors = line.match(/\d+:\d+\d+/g)
        if (!timeAnchors) {
            return
        }

        var _t = line.split("]");
        var text = _t[_t.length - 1];

        timeAnchors.forEach(function (anchor) {
            var _r = anchor.split(":").map(parseFloat)
            var time = 0;
            if (_r[0] > 0) time = _r[0] * 60;
            time += _r[1]
            //var time = (_r[0] * 60 + (Math.round(_r[1] * 10)) / 10) * 1000;
            //var p = _rn((time * 100) / window.playerTrackDuration, 1);
            result[_rn(time, 0)] = {
                text
            }
        })

    })

    return result;
}
window.showTimeComments = [];
window.canPlayAudioTrack = true;
window.isEmbedPlaying = false;
$(function() {

    reloadInit();
    $(document).on("click", ".confirm", function() {
        return confirm($(this).attr('href'), $(this).data('message'), $(this).data('ajax-action'));
    });
    recalculateWaveImages();
    $(window).scroll(function() {
        if($(window).scrollTop() + $(window).height() == $(document).height()) {
            paginateTracks();

            if ($(".main-loadmore-container").length > 0) {
                globalPaginate($(".main-loadmore-container").data('container'));
            }
        }
    });

    $('.sound-playlist-content').scroll(function() {
        if($(this).find('.content').height() - $(this).scrollTop() <= $('.sound-playlist-content').height()) {
            paginateSoundPlaylist();
        }
    })

    $(document).on('submit','.general-form',function() {
        var url = $(this).attr('action');
        var f = $(this);
        var btId = f.find('.ladda-button').prop('id');
        var btnName = "#" + f.find('.ladda-button').prop('id');
        if ($("#selected-songs").length > 0) {
            var canContinue = true;
            $('.track-file').each(function() {
                if ($(this).val() === '') canContinue = false;
            })

            $('#selected-songs .progress').each(function() {
                //alert($(this).css('display'));
                var n = $(this).data('id');
                if ($('.the-progress-wrapper-'+n+' .its-loader img').css('display') !== 'none') canContinue = false;
                if ($('.title-wave-' + n).data('loaded') === undefined) canContinue = false;
                if ($('.title-wave2-' + n).data('loaded') === undefined) canContinue = false;
                if ($('.demo-wave-' + n).length > 0 && $('.demo-wave-' + n).val() !== '' && $('.demo-wave-' + n).data('loaded') === undefined) canContinue = false;
                if ($('.demo-wave2-' + n).length > 0  && $('.demo-wave-' + n).val() !== '' && $('.demo-wave2-' + n).data('loaded') === undefined) canContinue = false;
            })

            if (!canContinue) return false;
        }
        if (f.data('not-ready') !== undefined && f.data('not-ready')) return false;
        if (btId !== undefined && btId !== '') showFormLoader(btnName);



        var progress = null;
        if (f.data('upload')) {
            progress = $(f.data('upload'));
            progressPercent = 0;
        }
        f.ajaxSubmit({
            url : url,
            uploadProgress: function(event, position, total, percentComplete) {
                var percentVal = percentComplete;

                if (progress !== null) {
                    progress.find('.progress-bar').css('width', percentVal + '%');
                    progress.find('.progress-bar').html(percentVal + '%');
                    progress.show();
                }
            },
            success : function(d) {
                try{
                    var r = jQuery.parseJSON(d);
                    $('body').click()//to remove unwanted dropdowns
                    var m = r.message;var t = r.type;var v = r.value;var c = r.content,modal = r.modal,table=r.table;
                    if (t === 'url') {
                        load_page(v);

                    } else if(t === 'function') {
                        if (v != undefined && v != '') eval(v)(c);
                    } else if(t === 'modal-function') {
                        $(modal).modal("hide");
                        if (v !== undefined && v !== '') eval(v)(c);
                    } else if(t === 'modal-url') {
                        $(c).modal("hide");
                        load_page(v)
                    } else if(t === 'reload') {
                        load_page(window.location.href);
                    } else if(t === 'reload-modal') {
                        $(c).modal("hide");
                        load_page(window.location.href);
                    } else if(t === 'normal-url') {
                        $(c).modal("hide");
                        window.location.href=v;
                    }
                    if (t === 'error' || t === 'error-function') {
                        notify(m, 'error');
                        if (v != undefined && v != '') eval(v)(c);
                    } else {
                        if (m !== '' ) notify(m, 'success');
                    }
                    if (btId !== undefined  && btId !== '') hideFormLoader(btnName);
                } catch (e) {
                    if (btId !== undefined  && btId !== '') hideFormLoader(btnName);
                }

                if (progress !== null) {
                    progress.hide();
                    progress.find('.progress-bar').css('width', '0%');
                    progress.find('.progress-bar').html('0%');
                }
            }
        });
        return false;
    })

    $(document).on('click', '[data-ajax=true]', function() {
        if($('.homepage-container').length > 0) return true;
        load_page($(this).attr('href'));
        return false;
    });



    $("#music-player").jPlayer({
        cssSelectorAncestor: '.sound-player',
        defaultPlaybackRate : 1,
        loadstart: function() {
            Hook.fire('play.song.ready', null, []);
        },
        play: function() {
            if (!window.canPlayAudioTrack) {
                $("#music-player").jPlayer('pause');
            }
        },
        play: function() {
            if (!window.canPlayAudioTrack) {
                $("#music-player").jPlayer('pause');
            }
        },
        ended: function () {
            if ($('#embed-body-container').length > 0) return false;
            if (window.sponsoredPlaying) {
                //window.repeatSong = false;
                return skipAds();
            }
            if (window.repeatSong) {
                console.log('Repeat song triggered')
                playSong(window.currentPlayingObject);
            } else {
                playNext();
            }
        },
        timeupdate : function(event) {
            var percent = Math.round(event.jPlayer.status.currentPercentRelative);
            var user = ".time-comment-" + percent + '-' + window.currentPlaying;

            if ($(user).length > 0 && jQuery.inArray(percent, window.showTimeComments) === -1) {
                $('body').click();
                var user = document.querySelector(user);
                window.showTimeComments.push(percent);
                var ttt = user._tippy;
                ttt.show();
                setTimeout(function() {
                    ttt.hide();
                }, 4500);
            } else {

            }
            if (window.playStopPercent > 0) {
                if(event.jPlayer.status.currentPercentRelative > window.playStopPercent) {
                    $(this).jPlayer('stop');
                    //show the buy dialog to prevent confusion
                    buy_item(window.playBuyType, window.playBuyTrack);
                }
            }
            Hook.fire('play.time.update', null, [event]);


            try {
                if (lyricPlayerTimeUpdate !== undefined) lyricPlayerTimeUpdate(event.jPlayer.status.currentPercentRelative);
            } catch (e){}
            if (window.playerLyrics != null) {
                //console.log(event.jPlayer.status);
                percent = _rn(event.jPlayer.status.currentTime, 0)
                //var cTime = _rn(event.jPlayer.status.currentTime, 2);
                //console.log(percent);
                if (window.playerLyrics[percent])  {
                    var v = window.playerLyrics[percent].text;
                    if (v == '') {
                        $('.player-lyrics-container').hide();
                    } else {
                        $('.player-lyrics-container').fadeIn();
                        $('.player-lyrics-container').html(v)
                    }
                }
            }

        },
        swfPath: baseUrl + 'assets/js/',
        supplied: "mp3,m4a",
        wmode: "window",
        volume: defaultVolume / 100,
        smoothPlayBar: true,
        keyEnabled: false
    });

    if (document.addEventListener)
    {
        document.addEventListener('webkitfullscreenchange', exitHandler, false);
        document.addEventListener('mozfullscreenchange', exitHandler, false);
        document.addEventListener('fullscreenchange', exitHandler, false);
        document.addEventListener('MSFullscreenChange', exitHandler, false);
    }

    function exitHandler()
    {
        if (document.webkitIsFullScreen || document.mozFullScreen || document.msFullscreenElement !== null)
        {
            setTimeout(function() {
                resizeVideo()
            }, 100)
        }
    }


    $(document).on('click', '.play-button', function() {
        if ($(this).data('embed') !== undefined) {
            window.isEmbedPlaying = true;
        }
        if (!$(this).hasClass('playlist-button')) {
            return playSong(this);
        }
        return false;
    });

    $(document).on('click', '.ajax-action', function() {
        ajaxAction($(this).attr('href'));
        return false;
    });
    if ($('.mejs__overlay-button').length > 0) $('.mejs__overlay-button').click();

    var yScrolled = 200;
    $(window).scroll(function() {
        if ($(this).scrollTop() > yScrolled) {
            $('.scrollup-btn').css({'bottom': '68px'});
        } else {
            $('.scrollup-btn').css({'bottom': '-68px'});
        }
    });
    $( document ).on( 'click', '.scrollup-btn', function(e){
        e.preventDefault();
        $("html, body").animate({ scrollTop: 0 }, 0);
    });
    $(document).on('mouseover', '.hover-show', function() {
        ///$(this).click();
    })

    $(document).on('mouseover', '.comments-container .each-comment', function() {
        var id = $(this).attr('id');
        $('#'+id+'-actions a').fadeIn();
    })
    $(document).on('mouseleave', '.comments-container .each-comment', function() {
        $(this).find('.actions a').hide();
    });

    $(document).on("keydown", function(key) {
        //alert(key.keyCode);
        if(key.keyCode == 32) {
            if($('input:focus, textarea:focus').length == 0 && $('.note-editor').length < 1) {
                // Prevent the key action
                key.preventDefault();
                if($("#music-player").data('jPlayer').status.paused) {
                    $("#music-player").jPlayer('play');
                } else {
                    $("#music-player").jPlayer('pause');
                }
            }
        }

        if(key.keyCode == 39) {
            if($('input:focus, textarea:focus').length == 0 && $('.note-editor').length < 1) {
                // Prevent the key action
                key.preventDefault();
                playNext();
            }
        }
        if(key.keyCode == 37) {
            if($('input:focus, textarea:focus').length == 0 && $('.note-editor').length < 1) {
                // Prevent the key action
                key.preventDefault();
                playprev()
            }
        }
        if(key.keyCode == 77) {
            if($('input:focus, textarea:focus').length == 0 && $('.note-editor').length < 1) {
                // Prevent the key action
                key.preventDefault();
                if($('.jp-unmute').is(':hidden')) {
                    $('.jp-mute').click();
                } else {
                    $('.jp-unmute').click();
                }
            }
        }

        if(key.keyCode == 82) {
            if($('input:focus, textarea:focus').length == 0 && $('.note-editor').length < 1) {
                // Prevent the key action
                key.preventDefault();
                $('.repeat-song-trigger').click();
            }
        }
    });

    $(document).on('click', '.reply-button', function() {
        //first hide all comments form opened

        var container = $(this).parent().parent().parent().parent();
        var display = container.find('.comment-form').css('display');
        $(".comments-container .comment-form").fadeOut();
        if (display !== 'none') {
            container.find('.comment-form').fadeOut();
        } else {
            container.find('.comment-form').fadeIn();
        }

        return false;
    });

    $(document).on('click', '.load-more-button', function() {
        globalPaginate($(this).data('container'));
        return false;
    });

    $(document).on('click', '.playlist-play-button', function() {
        playSong($('.track-play-button'));
        return false;
    });

    $(document).on('mouseover', '.track-hover-display-container', function() {
        // $('.track-hover-display').hide();
        $(this).find('.track-hover-display').fadeIn();
    });

    $(document).on('mouseleave', '.track-hover-display-container', function() {
        $(this).find('.track-hover-display').fadeOut();
    });

    $(document).on('mousedown', '.jp-volume-bar', function() {
        var parentOffset = $(this).offset(),
            width = $(this).width();
        $(window).mousemove(function(e) {
            var x = e.pageX - parentOffset.left,
                volume = x/width
            if (volume > 1) {
                $("#music-player").jPlayer("volume", 1);
            } else if (volume <= 0) {
                $("#music-player").jPlayer("mute");
            } else {
                $("#music-player").jPlayer("volume", volume);
                $("#music-player").jPlayer("unmute");
            }

            savePlayerVolume();
        });
        return false;
    });

    if(localStorage.getItem("store-volume") !== null) {
        $("#music-player").jPlayer("volume", localStorage.getItem("store-volume"));
    }

    $(document).on('mouseup', function() {
        $(window).unbind("mousemove");
    });

    $(document).on('submit', '.searchbar', function() {
        if ($(this).find('input[type=text]').val() === '') return false;
        var term = $(this).find('input[type=text]').val();
        var page = ($('#search-page').data('page') === undefined) ? '' : $('#search-page').data('page');
        term = term.replace('#', '');
        var param = [{key: 'term', value: term}, {key: 'page',value:page}];
        $('input').each(function() {
            if ($(this).attr('name') !== 'term') {
                param.push({key: $(this).attr('name'), value: $(this).val()})
            }
        })
        var url = buildLink("search", param)
        load_page(url);
        return false;
    });

    $(document).on('keyup', '.searchbar input', function() {
        if ($(this).val().length > 0 && $(window).width() > 700) {
            $(".search-dropdown-container").fadeIn();
            $('.search-dropdown-container').html('<div class="ms_bars mt-3"><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div></div>');

            var term = $(this).val();
            var page = ($('#search-page').data('page') === undefined) ? '' : $('#search-page').data('page');
            term = term.replace('#', '');
            var url = buildLink("search/dropdown", [{key: 'term', value: term}, {key: 'page',value:page}]);
            $.ajax({
                url : url,
                success : function(r) {
                    $('.search-dropdown-container').html(r);
                }
            });

            $(document).click(function(e) {
                if(!$(e.target).closest(".search-dropdown-container").length) $(".search-dropdown-container").hide();
            })
        }
    });

    $(document).click(function(e) {
        var trigger  = $("#show-notification-btn");
        if(!$(e.target).closest(".notification-dropdown-container").length && !$(e.target).closest(trigger).length) {
            $(".notification-dropdown-container").hide();
        }
    });

    setupBrowserNotification();

    setInterval(function() {
        if (window.browserNotification !== null) {
            //window.browserNotification.showNotification("This is mes")
        }
        if (isLoggedIn && !window.notificationIsChecking) {
            window.notificationIsChecking = true;
            //var push = (window.browserNotification !== null) ? true : false;
            $.ajax({
                url : buildLink('check/notification', [{key : 'time', value: lastTime},{key:'push',value:true}]),
                success : function(r) {
                    if(r === 'login') {
                        isLoggedIn = false;
                        return;
                    }

                    var result = jQuery.parseJSON(r);
                    lastTime = result.time;

                    if (result.notification > 0) {
                        var value = (result.notification > 99) ? '99+' : result.notification;
                        $('.notification-count').html(value);
                        $('.notification-count').fadeIn();
                    } else {
                        $('.notification-count').hide();
                    }

                    if (result.messages > 0) {
                        var value2 = (result.messages > 99) ? '99+' : result.messages;
                        $('.message-count').html(value2);
                        $('.message-count').fadeIn();
                    } else {
                        $('.message-count').hide();
                    }
                    var title = siteTitle;
                    if (window.isFocused) {

                    } else {
                        if (result.latestNotifications.length > 0) {
                            var body = "";
                            if (result.latestNotifications.length > 1) {
                                var count = result.latestNotifications.length -1;
                                body += strings.you_have_new+" "+ count + " "+ strings.notifications;
                                //window.browserNotification.showNotification(title, {body: body});
                                Push.create(title, {
                                    body: body,
                                    icon: 'icon.png',
                                    timeout: 8000,               // Timeout before notification closes automatically.
                                    vibrate: [100, 100, 100],    // An array of vibration pulses for mobile devices.
                                    onClick: function() {

                                    }
                                });

                            } else {
                                var obj = result.latestNotifications[0];
                                body = obj.full_title;
                                Push.create(title, {
                                    body: body,
                                    icon: obj.avatar,
                                    timeout: 8000,               // Timeout before notification closes automatically.
                                    vibrate: [100, 100, 100],    // An array of vibration pulses for mobile devices.
                                    onClick: function() {

                                    }
                                });
                                //window.browserNotification.showNotification(title, {body: body,image: obj.avatar});
                            }
                        }

                        if (result.latestMessages.length > 0) {
                            var body = "";
                            if (result.latestMessages.length > 1) {
                                var count = result.latestMessages.length -1;
                                body += strings.you_have_new+" "+ count + " "+ strings.messages;
                                Push.create(title, {
                                    body: body,
                                    icon: obj.avatar,
                                    timeout: 8000,               // Timeout before notification closes automatically.
                                    vibrate: [100, 100, 100],    // An array of vibration pulses for mobile devices.
                                    onClick: function() {

                                    }
                                });
                                //window.browserNotification.showNotification(title, {body: body});
                            } else {
                                var obj = result.latestMessages[0];
                                var theMessageContent = obj.message;
                                if (obj.trackid !== '0') {
                                    theMessageContent += (theMessageContent !== '') ? '\n '+ obj.track.title : obj.track.title;
                                }
                                if (obj.playlistid !== '0') {
                                    theMessageContent += (theMessageContent !== '') ? '\n '+ obj.playlist.name : obj.playlist.name;
                                }
                                body = obj.full_name+'\n '+theMessageContent;
                                //window.browserNotification.showNotification(title, {body: body,image: obj.avatar});
                                Push.create(title, {
                                    body: body,
                                    icon: obj.avatar,
                                    timeout: 8000,               // Timeout before notification closes automatically.
                                    vibrate: [100, 100, 100],    // An array of vibration pulses for mobile devices.
                                    onClick: function() {

                                    }
                                });
                            }
                        }
                    }
                    window.notificationIsChecking = false;
                }
            });
        }
    }, updateTime);


    $(document).on('click', '#stripeButton', function() {
        var type = $(this).data('type');
        var typeId = $(this).data('typeid');
        var price = $(this).data('price');
        var handler = StripeCheckout.configure({
            key: stripeKey,
            image: siteLogo,
            locale: 'auto',
            currency : $(this).data('currency'),
            token: function(token) {
                //console.log(token)
                $("#paymentMethodModal .loader").fadeIn();
                $.ajax({
                    url : buildLink('payment/stripe', [
                        {key: 'type', value: type},
                        {key: 'typeid', value: typeId},
                        {key: 'price', value: price},
                        {key: 'token', value: token.id}
                    ]),
                    success : function(r) {
                        r = jQuery.parseJSON(r);
                        $("#paymentMethodModal .loader").hide();
                        if (r.status === 1) {
                            $("#paymentMethodModal").modal('hide');
                            notify(r.message, 'success');
                            if (r.url.match("api/pay")) {
                                window.location.href = r.url;
                            } else {
                                load_page(r.url);
                            }

                        } else {
                            notify(r.message, 'error');
                        }
                    }
                })
            }
        });

        handler.open({
            name: $(this).data('title'),
            description: $(this).data('desc'),
            amount: $(this).data('price') * 100
        });

        // Close Checkout on page navigation:
        window.addEventListener('popstate', function() {
            handler.close();
        });

        return false;
    });


    $(window).on('blur', function() {
        window.isFocused = false;
    })
    $(window).on('focus', function() {
        window.isFocused = true;
    });

    $(document).on('click', '.navicon', function() {
        if ($('body').hasClass('side-menu-open')) {
            $('body').removeClass('side-menu-open')
        }  else {
            $('body').addClass('side-menu-open');
            //document.body.style.overflow = 'hidden';
        }
        return false;
    });

    $(document).on('click', '.navicon-2', function() {
        if ($('body').hasClass('side-collapsed')) {
            $('body').removeClass('side-collapsed');
            recalculateWaveImages();
        }  else {
            $('body').addClass('side-collapsed');
            window.autocollapsed = false;
            recalculateWaveImages();
            //document.body.style.overflow = 'hidden';
        }
        return false;
    });

    $(document).on('click', function() {
        $('body').removeClass('side-menu-open')
    });

    $(document).on('mouseover', '#side-menu', function(){
        $("#side-menu::-webkit-scrollbar").css('display', 'block');
    });

    var gdpr = getCookie('gdpr');
    if (gdpr === '') {
        $('.gdpr-container').fadeIn();
    }
    ///$('.gdpr-container').fadeIn();
    $(window).resize(function() {
        recalculateWaveImages();
    });

    $(document).on('click', '.track-time-comment-container .pane', function(e) {
        console.log(e);
        if (e.target.nodeName === 'DIV') {
            var posX = $(e.target).position().left;
            var parent = $(this).parent();
            var left = e.offsetX;
            clicker = $(this).find('.clicker');
            if (clicker.length > 0) {
                clicker.css('left',  left+'px');
                clicker.fadeIn();

                parent.find('form').fadeIn();
                parent.find('form .reply-to').hide();
                parent.find('form .reply-to input').val('');
                parent.find('form').removeClass('reply');
                parent.find('form .input input').focus();
                var playerWidth = $(this).width();
                var duration = $(this).data('seconds');
                var atValue = (left * 100) / playerWidth;
                atValue = (duration * atValue)  / 100;
                parent.find('form .at-value').val(atValue);
            }
        }
    });

    $(document).on('click', '.playprogress', function(e) {
        var posX = $(e.target).position().left;
        var parent = $(this).parent();
        var left = e.offsetX;
        var trackId  = $(this).data('id');
        var duration = $(this).data('duration');

        if (window.currentPlaying !== trackId) {
            $('.track-play-button-'+trackId).trigger('click');
        }
    });

    $(document).on('click', '.jp-volume-bar', function() {
        savePlayerVolume()
    })

});

function savePlayerVolume() {

    setTimeout(function() {
        // Get the style attribute value
        var volume = $(".jp-volume-bar-value").attr("style");
        var volume = volume.replace("width: ", "");

        if(volume !== "100%;") {
            var volume = volume.substring(0, 2).replace(".", "").replace("%", "");
        }

        if(volume.length === 1) {
            var volume = "0.0"+volume;
        } else if(volume.length == 2) {
            var volume = "0."+volume;
        } else {
            var volume = 1;
        }
        localStorage.setItem("store-volume", volume);
    }, 1);
}

function recalculateWaveImages() {
    var width = $('.wave-possible-play-control').width();

    $('.play-controls .playprogress img, .playprogress-revert img').each(function() {
        var p = $(this).parent().width();
        ///console.log(p);
        $(this).css('width', width+'px');
    });
    //console.log('am working');
    $(".current-seek .jp-progress > .current-seek-wave ").each(function() {
        var p = $(this).parent().width();
        $(this).css('width', width+'px');
    });
    $(".current-seek .jp-progress .jp-seek-bar .jp-play-bar .current-seek-wave").each(function() {
        var p = $(this).parent().parent().width();
        $(this).css('width', width+'px');
    });
}

function prepare_track_time_comments() {
    $('.track-time-comment-container .pane a.clicker').draggable({
        containment: "parent",
        axis: "x",
        zIndex: 100,
        stop: function( event, ui ) {
            var subParent = $(ui.helper).parent();
            var parent = subParent.parent();

            parent.find('form .input input').focus();
            var playerWidth = subParent.width();
            var duration = subParent.data('seconds');
            var atValue = (ui.position.left * 100) / playerWidth;
            atValue = (duration * atValue)  / 100;
            parent.find('form .at-value').val(atValue);
        }
    });

    //lets gather ids of tracks that have not been loaded
    var ids = "";
    $(".track-time-comment-container").each(function() {
        if ($(this).data('loaded') === undefined) {
            ids += (ids != '') ? ','+$(this).data('id') : $(this).data('id');
        }
    });

    if (ids !== '') {
        loadTrackTimeComments(ids);
    }

}

function loadTrackTimeComments(ids) {
    var playerWidth = $('.track-time-comment-container').width();
    $.ajax({
        url : buildLink('comment/time/load', [{key: 'ids', value: ids},{key:'width', value: playerWidth}]),
        success : function(r) {
            var json = jQuery.parseJSON(r);
            jQuery.each(json, function(id, content) {
                $(".track-time-comment-container-" + id + ' .pane .users').html(content);
                tippy('.mtitle-light',{
                    animation: 'shift-toward',
                    arrow: true,
                    interactive : true,
                    theme : 'light',
                    allowTitleHTML: true,
                }); // for quick reload of the title
            });
        }
    })
}

function reply_time_comment(trackid, username, commentid, atValue) {
    var parent = $('.track-time-comment-container-'+trackid);
    parent.find('form').fadeIn();
    parent.find('form').addClass('reply');
    parent.find('form .input input').focus();
    parent.find('form .at-value').val(atValue);
    parent.find('form .reply-to').fadeIn();
    parent.find('.clicker').hide();
    parent.find('form .reply-to .text').html('<b>@'+username+'</b>');
    parent.find('form .reply-to input').val(commentid);
    return false;
}
window.browserNotification = null;
window.notificationIsChecking = false;
function setupBrowserNotification() {
    try{
        Push.Permission.request();
    } catch (e){}
}

function playlist_play(id) {
    playSong(".playlist-tracks-list-" + id + " .play-button");
    return false;
}

function hideSearchDropdown() {
    $(".search-dropdown-container").fadeOut();
    return false;
}

function show_notification_dropdown(t) {
    $(".notification-dropdown-container").fadeIn();
    $('.notification-dropdown-container .content').html('<div class="ms_bars mt-3"><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div></div>');

    var trigger = $(t);


    $.ajax({
        url : buildLink('notification/dropdown'),
        success : function(r) {
            $('.notification-dropdown-container .content').html(r);
            reloadInit();
        }
    });


    return false;
}

function resizeVideo() {

    return false;
    $('video,.mejs__container').css('width', '100%');
    $('video').css('height', '100%');
    $('.mejs__container').css('height',  '4000px');
    console.log('yay')
}

function load_payment_method(price, type, typeId) {
    var m = $("#paymentMethodModal");
    pageLoader();
    m.modal('show');

    $.ajax({
        url : buildLink('payment/method', [{key : 'type', value: type}, {key: 'typeid', value:typeId}, {key:'price', value: price}]),
        success : function(r) {
            m.find('.modal-body').html(r);
            pageLoaded();
            hideFullLoading();
        }
    });

    return false;
}

function addDownload(id) {
    $.ajax({
        url : buildLink('track/add/download', [{key: 'id', value: id}])
    });
}

function share(id, type) {
    $("#shareModal .modal-body").html('');
    $("#shareModal").modal("show");
    $.ajax({
        url : buildLink('load/share', [{key:'id','value':id},{key:'type',value:type}]),
        success: function(r) {
            $("#shareModal .modal-body").html(r);
            reloadInit();
        }
    });

    return false;
}

function socialShare(type, link,title,art) {
    if(type == 1) {
        window.open("https://www.facebook.com/sharer/sharer.php?u="+link, "", "width=500, height=250");
    } else if(type == 2) {
        window.open("https://twitter.com/intent/tweet?text="+title+"&url="+link, "", "width=500, height=250");
    } else if(type == 3) {
        window.open("https://plus.google.com/share?url="+link, "", "width=500, height=250");
    } else if(type == 4) {
        window.open("https://pinterest.com/pin/create/button/?url="+link+"&description="+title+"&media="+art, "", "width=500, height=250");
    } else if(type == 5) {
        window.open("mailto:?Subject="+title+"&body="+title+" - "+link, "_self");
    }
    return false
}

window.currentRenderType = '';

function changeEmbedType(t, type, tt) {
    var t = $(t);
    $('.embed-types a').removeClass('active');
    window.currentRenderType = type;
    updateEmbedPreview();
    t.addClass('active');
    return false;
}

function updateEmbedPreview() {
    var url = $("#embed-code").data('url');
    var height = 250;
    var color = $("#embed-color").val().replace('#','');
    $auto = ($('.autoplay-checkbox').prop('checked') !== false);
    var type = window.currentRenderType;
    var tt = $("#embed-code").data('type');
    if (type) {

        if ($auto) {
            url = buildLink(url, [{key:'type',value:type},{key: 'autoplay', value: 1},{key:'color', value: color}]);
        } else {
            url = buildLink(url, [{key:'type',value:type},{key:'color', value: color}]);
        }
        height = (type === 'mini') ? 70 : 140;
    } else {
        if ($auto) {
            url = buildLink(url, [{key:'autoplay', value: 1},{key:'color', value: color}]);
        } else {
            url = buildLink(url,[{key:'color', value: color}]);
        }
    }


    if (tt === 'playlist') {
        height += 150
    }
    var iframe = '<iframe width="100%" height="'+height+'" frameborder="no" scrolling="no" src="'+url+'"></iframe>';
    $("#embed-code").val(iframe);
    $(".preview-container").html(iframe);
}

function showMessage(id) {
    $("#messageModal").modal('show');
    $.ajax({
        url : buildLink('message/form', [{key:'id','value':id}]),
        success: function(r) {
            $("#messageModal .modal-body").html(r);
            reloadInit();
        }
    });
    return false;
}

function reloadStatistics() {
    var url = buildLink('statistics', [
        {key: 'id',value: $('#stats-id').val()},
        {key : 'time', value: $('#filter-time').val()},
        {key : 'chart', value: $('#filter-chart').val()}
    ]);
    load_page(url);
}

function submitForm(id) {
    $(id).submit();
}

function effectColor(t, target, type) {
    var color = $(t).val();
    switch (type) {
        case 'background':
            $(target).css('background-color', color );
            break;
        case 'color':
            $(target).css('color', color );
            break;
        case 'border':
            $(target).css('border-color', color );
            break;
        case 'border-bottom':
            $(target).css('border-bottom-color', color );
            break;
    }
}

function reloadCaptcha() {
    document.getElementById('captcha').src = baseUrl+'/captcha/securimage_show.php?' + Math.random();
}

function setCookie(cname, cvalue, exdays) {
    if(exdays == undefined) exdays = 365;
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while(c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if(c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function deleteCookie(cname) {
    document.cookie = cname + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC";
}

function acceptCookie() {
    setCookie('gdpr', '1');
    $('.gdpr-container').fadeOut();
    return false;
}

function reloadPeopleSuggestions() {
    $.ajax({
        url : buildLink('users/suggestion'),
        success : function(r) {
            $('.people-suggestion-list').html(r);
        }
    });
    return false;
}

function spotlightGlobalAdded(r) {
    var json = jQuery.parseJSON(r);
    $('#admin-track-list-'+json.id).removeClass('btn-success');
    $('#admin-track-list-'+json.id).removeClass('btn-secondary');
    $('#admin-track-list-'+json.id).addClass(json.class);
    $('#admin-track-list-'+json.id).attr('title', json.title);
    reloadInit()
}

function spotlightlistUpdated(c) {
    $('.spotlight-edit-tracks').html(c);
    $('.spotlighttrackinput')[0].selectize.clear();
}

function showFullLoading() {
    // $('.full-loader').slideDown();
}

function hideFullLoading() {
    // $('.full-loader').hide();
}

function buy_item(type, id) {
    if (!isLoggedIn) {
        changeAuthModal('.login-form');
        return false;
    }

    showFullLoading();
    $.ajax({
        url : buildLink('store/confirm/buy', [{key: 'type', value: type},{key:'id', value: id}]),
        success : function(r) {

            r = jQuery.parseJSON(r);
            if (r.status === 1) {
                //we can show the payment methods
                load_payment_method(r.price, r.type,r.id);
            } else {
                hideFullLoading();
                notify(r.message, r.type);
            }
        }
    });

    return false;
}

function load_store_browse(t, w) {
    load_page(buildLink('store/browse', [{key: 'which', value: w}, {key:'genre', value: $(t).val()}]));
    return false;
}

function change_theme_mode(mode) {
    if ($('body').hasClass('light-mode') === true) {
        $('body').addClass('dark-mode');
        $('body').removeClass('light-mode');
        mode = 'dark-mode';
    } else {
        $('body').removeClass('dark-mode');
        $('body').addClass('light-mode');
        mode = 'light-mode'
    }
    if ($('#hidden-wave-replace').length > 0) {
        replaceAllWaveColor(mode);
    }
    $.ajax({
        url : buildLink('save/theme/mode', [{key:'mode', value: mode}])
    });
    return false;
}

function replaceAllWaveColor(mode) {
    if (mode === 'dark-mode') {
        str = 'black';
        search = 'white'
    } else {
        str = 'white';
        search = 'black'
    }

    console.log(search)
    $('.wave-image').each(function() {
        src = $(this).attr('src');
        v = src.replace(search, str);
        $(this).attr('src', v);
        $(this).prop('src', v);
        console.log(v);
    });
}

window.uploadPostType = 'song'
function switch_upload_type(type) {
    $('.upload-types').hide();
    window.uploadPostType = type;
    switch (type) {
        case 'song':
            $('.uploader-form').fadeIn();
            break;
        case 'album':
            $('.create-playlist-container').fadeIn();
            $('.album-type-title').show();
            $('.playlist-type-title').hide();
            $('.upload-playlist-type').val(0);
            if ($(".upload-pricing").length > 0) {
                $('.upload-album-price').fadeIn();
            }
            break;
        case 'playlist':
            $('.create-playlist-container').fadeIn();
            $('.album-type-title').hide();
            $('.playlist-type-title').show();
            $('.upload-playlist-type').val(1);
            $('.upload-album-price').hide();
            break;
    }
    return false;
}

function start_upload_playlist() {
    if($('.upload-playlist-title').val() === "") return false;
    $('.create-playlist-container').hide();
    $('.uploader-form').fadeIn();
    return false;
}

function refresh_upload_type() {
    $('.uploader-form').hide();
    $('.create-playlist-container').hide();
    $('.upload-types').fadeIn();
    return false;
}

function fetchVideo() {
    var input = $("#video-url");
    if (input.val() === '') return false;

    $.ajax({
        url : buildLink('video/fetch', [{key : "url", value: input.val()}]),
        success : function(data) {
            var json = jQuery.parseJSON(data);
            if (json.status === 0) {
                notify(json.message, 'error');
            } else {
                console.log(json)
                // notify(json.message, 'error');
                $('.video-selector').hide();
                $('#video-form-container').fadeIn();
                $('.video-title').val(json.title);
                $('.video-description').val(json.description);
                $('.video-art').val(json.thumbnail);
                $('.video-duration').val(json.duration);
                $('.video-source').val(json.type);
                $('.video-link').val(json.link);
                //$('.video-tags').val(json.tags);
                console.log(json.duration);
                $('#songAvatar').css('background-image','url('+json.thumbnail+')');
                $('#songAvatar').show();
                // alert();
                var tags = json.tags.split(',');
                for(i=0;i<tags.length;i++) {
                    window.globalSelect[0].selectize.createItem(tags[i]);
                }

            }
        }
    });
    return false;
}

function show_more(t) {
    var o = $(t);
    if (o.attr('data-type') === 'expand') {
        $('.video-description').addClass('desc-expanded');
        o.attr('data-type', 'expanded');
        o.html(o.data('less'))
    } else {
        $('.video-description').removeClass('desc-expanded');
        o.attr('data-type', 'expand');
        o.html(o.data('more'))
    }

    return false;
}

function reloadVideoSuggestions() {
    $.ajax({
        url : buildLink('video/reload/suggestion'),
        success : function(d) {
            $('.video-suggestion-list').html(d);
        }
    })
    return false;
}

function addWatchLater(r) {
    var json = jQuery.parseJSON(r);
    if(json.which === 1) {
        $('.add-watch-btn').attr('title', json.remove);
        $('.add-watch-btn').addClass('colored-bg color-white')
    } else {
        $('.add-watch-btn').attr('title', json.add);
        $('.add-watch-btn').removeClass('colored-bg color-white')
    }
}
var currentSpeedIdx = 0;
var speeds = [ 1, 1.5, 2, 2.5,3  ];
function playerSpeedTrigger(t) {
    var obj = $(t);
    currentSpeedIdx = currentSpeedIdx + 1 < speeds.length ? currentSpeedIdx + 1 : 0;

    jQuery("#music-player").jPlayer("option","playbackRate", speeds[currentSpeedIdx]);

    obj.html( speeds[currentSpeedIdx] + 'x' );
    return false;
}
window.adminRegeneratingWave = false;
function admin_generate_waves(t, s) {
    var t = $(t);
    if (!window.adminRegeneratingWave) {
        window.adminRegeneratingWave = true;
        $.ajax({
            url : buildLink('admin/update/wave/images', [{key: 'server', value: s}]),
            success : function(r) {
                var json = jQuery.parseJSON(r);
                notify(json.message, 'success');
                do_admin_generate_waves(0, json.total, s);
            }
        })

    }
    return false;
}

function do_admin_generate_waves(f, t, s) {
    $.ajax({
        url : buildLink('admin/update/wave/images', [
            {key:'from', value : f},
            {key: 'start', value: true},
            {key : 'total', value: t},
            {key: 'server', value: s}
        ]),
        success : function(r) {
            var json = jQuery.parseJSON(r);
            notify(json.message, 'success');
            if (json.continue) {
                do_admin_generate_waves(json.from, json.total, s);
            }

        }
    });
}