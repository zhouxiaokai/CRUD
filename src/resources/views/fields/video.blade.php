<!-- text input -->
<div data-video @include('crud::inc.field_wrapper_attributes') >
    <label for="{{ $field['name'] }}_link}">{!! $field['label'] !!}</label>
    <div class="video-preview">
        <a class="video-previewLink hidden" target="_blank" href="" title="">
            <i class="fa video-previewIcon"></i>
        </a><img class="video-previewImage" alt="" src="" />
    </div>
    <input class="video-link form-control" type="text" name="{{ $field['name'] }}_link" id="{{ $field['name'] }}_link">
    <input class="video-json" type="hidden" name="{{ $field['name'] }}" value="{{ old($field['name']) ? (old($field['name'])) : (isset($field['value']) ? ($field['value']) : (isset($field['default']) ? ($field['default']) : '' )) }}">

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
</div>

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@if ($crud->checkIfFieldIsFirstOfItsType($field, $fields))

    {{-- FIELD CSS - will be loaded in the after_styles section --}}
    @push('crud_fields_styles')
    {{-- @push('crud_fields_styles')
        {{-- YOUR CSS HERE --}}
        <style media="screen">
            .video-preview {
                margin-bottom: 10px;
                display: none;
            }
            .video-previewLink {
                 color: #fff;
                 display: inline-block;
                 width: 30px; height: 25px;
                 text-align: center;
                 border-top-left-radius: 3px; border-bottom-left-radius: 3px;
                 transform: translateY(-1px);
            }
            .video-previewLink.youtube {
                background: #DA2724;
            }
            .video-previewLink.vimeo {
                background: #00ADEF;
            }
            .video-previewIcon {
                transform: translateY(2px);
            }
            .video-previewImage {
                height: 25px;
                border-top-right-radius: 3px; border-bottom-right-radius: 3px;
            }
        </style>
    @endpush

    {{-- FIELD JS - will be loaded in the after_scripts section --}}
    @push('crud_fields_scripts')
        {{-- YOUR JS HERE --}}
        <script>
            jQuery(document).ready(function($) {

                var tryYouTube = function( link ){

                    var id = null;

                    // RegExps for YouTube link forms
                	var youtubeStandardExpr = /^https?:\/\/(www\.)?youtube.com\/watch\?v=([^?&]+)/i; // Group 2 is video ID
                	var youtubeAlternateExpr = /^https?:\/\/(www\.)?youtube.com\/v\/([^\/\?]+)/i; // Group 2 is video ID
                	var youtubeShortExpr = /^https?:\/\/youtu.be\/([^\/]+)/i; // Group 1 is video ID
                	var youtubeEmbedExpr = /^https?:\/\/(www\.)?youtube.com\/embed\/([^\/]+)/i; // Group 2 is video ID

                    var match = link.match(youtubeStandardExpr);

                    if (match != null){
                        id = match[2];
                    }
                    else {
                        match = link.match(youtubeAlternateExpr);

                        if (match != null) {
                            id = match[2];
                        }
                        else {
                            match = link.match(youtubeShortExpr);

                            if (match != null){
                                id = match[1];
                            }
                            else {
                                match = link.match(youtubeEmbedExpr);

                                if (match != null){
                                    id = match[2];
                                }
                            }
                        }
                    }

                    return id;
                };

                var tryVimeo = function( link ){

                    var id = null;
                    var regExp = /(http|https):\/\/(www\.)?vimeo.com\/(\d+)($|\/)/;

                    var match = link.match(regExp);

                    if (match){
                        id = match[3];
                    }

                    return id;
                };

                var fetchYouTube = function( videoId, callback ){

                    var api = 'https://www.googleapis.com/youtube/v3/videos?id='+videoId+'&key=AIzaSyDQa76EpdNPzfeTAoZUut2AnvBA0jkx3FI&part=snippet';

                    var video = {
                        provider: 'youtube',
                        id: null,
                        title: null,
                        image: null,
                        url: null
                    };

                    $.getJSON(api, function( data ){

                        if (typeof(data.items[0]) != "undefined") {
                            var v = data.items[0].snippet;

                            video.id = videoId;
                            video.title = v.title;
                            video.image = v.thumbnails.maxres ? v.thumbnails.maxres.url : v.thumbnails.default.url;
                            video.url = 'https://www.youtube.com/watch?v=' + video.id;

                            callback(video);
                        }
                    });
                };

                var fetchVimeo = function( videoId, callback ){

                    var api = 'http://vimeo.com/api/v2/video/' + videoId + '.json?callback=?';

                    var video = {
                        provider: 'vimeo',
                        id: null,
                        title: null,
                        image: null,
                        url: null
                    };

                    $.getJSON(api, function( data ){

                        if (typeof(data[0]) != "undefined") {
                            var v = data[0];

                            video.id = v.id;
                            video.title = v.title;
                            video.image = v.thumbnail_large || v.thumbnail_small;
                            video.url = v.url;

                            callback(video);
                        }
                    });
                };

                var parseVideoLink = function( link, callback ){

                    var response = {success: false, message: 'unknown error occured, please try again', data: [] };

                    try {
                        var parser = document.createElement('a');
                    } catch(e){
                        response.message = 'Please post a valid youtube/vimeo url';
                        return response;
                    }


                    var id = tryYouTube(link);

                    if( id ){

                        return fetchYouTube(id, function(video){

                            if( video ){
                                response.success = true;
                                response.message = 'video found';
                                response.data = video;
                            }

                            callback(response);
                        });
                    }
                    else {

                        id = tryVimeo(link);

                        if( id ){

                            return fetchVimeo(id, function(video){

                                if( video ){
                                    response.success = true;
                                    response.message = 'video found';
                                    response.data = video;
                                }

                                callback(response);
                            });
                        }
                    }

                    response.message = 'We could not detect a YouTube or Vimeo ID, please try obtain the URL again'
                    return callback(response);
                };

                var updateVideoPreview = function(video, container){

                    var pWrap = container.find('.video-preview'),
                        pLink = container.find('.video-previewLink'),
                        pImage = container.find('.video-previewImage'),
                        pIcon  = container.find('.video-previewIcon');

                    pLink.attr('href', video.url).attr('title', video.title).removeClass('youtube vimeo hidden').addClass(video.provider);
                    pImage.attr('src', video.image).attr('alt', video.title);
                    pIcon.addClass('fa-' + video.provider);
                    pWrap.fadeIn();
                };

                // Loop through all instances of the video field
                $("[data-video]").each(function(index){

                    var $this = $(this),
                        jsonField = $this.find('.video-json'),
                        linkField = $this.find('.video-link');

                        try {
                            var videoJson = JSON.parse(jsonField.val());
                            jsonField.val( JSON.stringify(videoJson) );
                            linkField.val( videoJson.url );
                            updateVideoPreview(videoJson, $this);
                        }
                        catch(e){
                            jsonField.val('');
                            linkField.val('');
                        }

                    linkField.on('focus', function(){
                        linkField.originalState = linkField.val();
                    });

                    linkField.on('blur', function(){

                        if( linkField.originalState != linkField.val() ){

                            if( linkField.val().length ){

                                parseVideoLink( linkField.val(), function( videoJson ){

                                    console.log(videoJson);

                                    if( videoJson.success ){
                                        linkField.val( videoJson.data.url );
                                        jsonField.val( JSON.stringify(videoJson.data) );
                                        updateVideoPreview(videoJson.data, $this);
                                    }
                                    else {
                                        alert(videoJson.message);
                                    }
                                });
                            }
                            else {
                                jsonField.val('');
                                $this.find('.video-preview').fadeOut();
                            }
                        }
                    });
                });
            });
        </script>

    @endpush
@endif
{{-- End of Extra CSS and JS --}}
{{-- ########################################## --}}