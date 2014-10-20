$(document).ready(function() {
    var btn;
    var somethingChanged = false;
    var category = {};

    $(window).keydown(function(e){
        if(e.keyCode == 13) {
            e.preventDefault();
            return false;
        }
    });

    $('.btn').on('click', function(e) {
        btn = $(this).attr('id');
    });

    $('.subnav').on('click', function(e) {
        var id = $(this).attr('id');
        $('.component').hide();
        $('#panel_' + id).show();
        $('#grouppanel_1').show();
        $('#grouppanel_2').show();
        $('#grouppanel_3').show();
        $('#grouppanel_4').show();

    });

    $('.glyphicon-remove-circle').on('click', function(e) {
        var id = $(this).attr('id');
        $('#panel_' + id).hide();
    });

    $('#editCategoriesEditor').on('click', function(e) {
        $('#panel_globalsCategory').show();
    });

    $('.glyphicon-resize-small').on('click', function(e) {
        $(this).hide();
    });


    /* ***********************************
     * FILE UPLOAD
     * *********************************** */
    $('.kv-fileinput-upload').on('click', function(e){
        e.preventDefault();

        var imgsrc = '<?php echo $this->gif;?>';
        var formData = new FormData();
        var data = {};
        var nodeList = $(document).find($('[type="file"]'));

        formData.append('templateId', $('#templateId').attr('value'));
        formData.append('advertiserId', $('#advertiserId').attr('value'));
        formData.append('companyId', $('#companyId').attr('value'));
        formData.append('action', 'upload');

        for (var i = 0; i < nodeList.length; i++)
        {
            var myId = nodeList[i].getAttribute('id');
            var targetId = myId.replace('_input', '');
            var fileSelect = $("#" + myId);

            var files = fileSelect.prop("files");

            if(files.length > 0)
            {
                var file = files[0];
                formData.append(targetId, file);
            }
        }

        var xhr =  new XMLHttpRequest();
        xhr.upload.addEventListener('load', onloadHandler, false);
        xhr.onreadystatechange = function(e) {
            if(xhr.readyState == 4) {
                response = $.parseJSON(xhr.response);
                imgsrc = response.imgsrc;
                $("#previewImage img").attr('src', imgsrc + '?' + new Date().getTime());
            }
        };
        xhr.open('POST', '/chameleon/ajax/changeSvg.php', true);
        xhr.send(formData);
        return false;

        function onloadHandler(e, args) {
            // $('#previewalert').show();
            response = $.parseJSON(xhr.response);
            imgsrc = response.imgsrc;
            $("#previewImage img").attr('src', imgsrc + new Date().getTime());
        }

        function onloadstartHandler() {
        }

        function onprogressHandler() {
        }
    });


    $("#previewImage img").mapster({
        fillColor: 'ff005',
        fillOpacity: 0.1,
        strokeWidth: 3,
        stroke: true,
        strokeColor: 'ff0000',
        singleSelect: true,
        clickNavigate: false
    });


    /* ***********************************
     * PREVIEW BUTTON
     *********************************** */
    $('#editor').on('submit', function(e){
        e.preventDefault();
        var action = btn;
        var xhr = new XMLHttpRequest();
        $("#previewImage img").unbind('mapster');

        xhr.onreadystatechange = function(e) {
            if(xhr.readyState == 4) {
                if(action === 'save') {
                    somethingChanged = false;
                }
                response = $.parseJSON(xhr.response);
                imgsrc = response.imgsrc;
                $("#previewImage img").unbind('mapster');
                $("#previewImage img").attr('src', imgsrc + '?ts=' + new Date().getTime());
            }
        }


        var formData = new FormData();

        /* ******************************************************************** */
        var nodeList = $(document).find($('[type="file"]'));

        for (var i = 0; i < nodeList.length; i++)
        {
            var myId = nodeList[i].getAttribute('id');
            var targetId = myId.replace('_input', '');
            var fileSelect = $("#" + myId);

            var files = fileSelect.prop("files");

            if(files.length > 0)
            {
                var file = files[0];
                formData.append(targetId, file);
            }
        }
        /* ******************************************************************** */

        var data = $('#editor').serializeArray(); // + "&action=" + btn;

        formData.append('action', action);

        $.each(data, function(key, inputfield) {
            formData.append(inputfield.name, inputfield.value);
        });

        xhr.open('POST', '/chameleon/ajax/changeSvg.php', true);
        xhr.send(formData);
    });

    $('.picker').colorpicker();

    $("#fileUpload").fileinput();

    $('#awesomeEditor input').change(function() {
        somethingChanged = true;
    });

    $('#overview').click(function() {
        if(somethingChanged === true) {
            var leaveConfirm = confirm('Unsaved changes detected! Continue?');
            if(true === leaveConfirm){
                window.location.href = "index.php?page=overview";
            }
        }
        else
        {
            window.location.href = "index.php?page=overview";
        }
        return false;
    });

    $("#category").change(function() {
        $( "#category option:selected" ).each(function() {

            var key = $(this).attr('value');
            var value = $(this).attr('title');
            category[key] = value;
        });
    })
    .trigger( "change" );


    $('body').on('click', '#addCategory', function(e) {
        var data = {};
        var categoryId    = $('#category').find(':selected').val()
        var categoryName  = $.trim($('#category').find(':selected').text());
        data.templateId   = '<?php echo $this->templateId; ?>';
        data.categoryId   = categoryId;
        data.categoryName = categoryName;
        data.advertiserId = $('#advertiserId').attr('value');
        data.companyId    = $('#companyId').attr('value');
        var pstdata = JSON.stringify(data);
        $.ajax({
            type: "POST",
            data: data,
            dataType: "json",
            url: "/chameleon/ajax/addCategory.php"
        }).done(function(){
        }).fail(function(){
            $('#categoryContainer').load('ajax/categoriesSelection.inc.php?templateId=<?php echo $this->templateId; ?>');
            var node = '<input type="text" disabled="disabled" id="subscription_' + categoryId + '" value="' + categoryName + '">';
            $('#global_categories').append(node);
        });
    });

    $('body').on('click', '#categoryContainer .removeCategory', function(e) {
        var data = {};
        data.templateId   = '<?php echo $this->templateId; ?>';
        var categoryId    = $(this).attr('id');
        data.categoryId   = categoryId;
        data.categoryName = $.trim($('#category').find(':selected').text());
        data.advertiserId = $('#advertiserId').attr('value');
        data.companyId    = $('#companyId').attr('value');
        $.ajax({
            type: 'POST',
            data: data,
            dataType: "json",
            url:  '/chameleon/ajax/removeCategory.php'
        }).done(function(){
        }).fail(function(){
            $('#row_' + categoryId).remove();
            $('#subscription_' + categoryId).remove();
        });
    });

    $('.preset').click(function(){
        var identifier = $(this).attr('id').split('#');

        switch(identifier[1])
        {
            case "primary":
            {
                $('#panel_'+identifier[0]+' #fill').val($('#primary-color').val());
                break;
            }
            case "secondary":
            {
                $('#panel_'+identifier[0]+' #fill').val($('#secondary-color').val());
                break;
            }
            case "presetFont":
            {
                var id = identifier[0] + "#fontfamily";

                $('select option').filter(function() {
                    return $(this).text() == $('#presetFontFamily option:selected').text();
                }).prop('selected', true);
                break;
            }
        }
    });

    //handles the enabling/disabling of the shadow form elements
    $('#shadowCheckBox.myCheckbox').click(function(e) {
        var id = $(this).attr('value');

        if($(this).is(":checked"))
        {
            $(this).attr("checked", true);
            $("#" + id + "_shadowColor").attr('disabled', false).addClass('picker');
            $("#" + id + "_shadowDist").attr('disabled', false);
        }
        else
        {
            $(this).attr("checked", false);
            $("#" + id + "_shadowColor").attr('disabled', true).removeClass('picker');
            $("#" + id + "_shadowDist").attr('disabled', true);
        }
    });

    //handles the enabling/disabling of the stroke form elements
    $('#strokeCheckBox.myCheckBox').click(function(e) {
        var id = $(this).attr('value');

        if($(this).is(":checked"))
        {
            $(this).attr("checked", true);
            $("#" + id + "_strokeColor").attr('disabled', false).addClass('picker');
            $("#" + id + "_strokeWidth").attr('disabled', false);
        }
        else
        {
            $(this).attr("checked", false);
            $("#" + id + "_strokeColor").attr('disabled', true).removeClass('picker');
            $("#" + id + "_strokeWidth").attr('disabled', true);
        }
    });

    // TODO: add a "initial" element to ALL templates?!
    $('area#head_large').trigger('click');
    $('#grouppanel_2').show();
});
