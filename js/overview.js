$(document).ready(function()
{
    $('#addCategory').click(function(e) {
        var selectedOpts = $('#availableCategory option:selected');
        if (selectedOpts.length == 0) {
            alert("Nothing to move.");
            e.preventDefault();
        }

        $('#assignedCategory').append($(selectedOpts).clone());
        $(selectedOpts).remove();
        e.preventDefault();
    });

    $('#removeCategory').click(function(e) {
        var selectedOpts = $('#assignedCategory option:selected');
        if (selectedOpts.length == 0) {
            alert("Nothing to move.");
            e.preventDefault();
        }

        $('#availableCategory').append($(selectedOpts).clone());
        $(selectedOpts).remove();
        e.preventDefault();
    });

    $('.carousel').carousel({
        interval: 4000
    });

    $('.addCategoryOverview').click(function(e) {
        $(".modal-body form").block({
            message: '<h1>Assigning categories...</h1>',
            css: { border: '3px solid #a00' }
        });
        e.preventDefault();
        var id = $(this).attr('id').split('-');
        var templateId = id[1];
        var advertiserId = id[2];
        var data = {};
        var category = [];

        $('#availableCategory-'+templateId).find('option:selected').each(function(i,selected){
            var subscription = {};
            subscription.id = $(selected).val();
            subscription.name = $.trim($(selected).text());
            category.push(subscription);
        });

        data.category = category;
        data.advertiserId = advertiserId;
        data.templateId   = templateId;
        data.companyId    = $('#companyId').attr('value');

        $.ajax({
            type: "POST",
            data: data,
            dataType: "json",
            url: "/chameleon/ajax/addCategory.php"
        }).done(function(){
        }).fail(function(){
            category.forEach(function(singleCategory){
                var item = '<div id="'+singleCategory.id+'" class="row"><p class="text-left overviewTitle categoryItem">'+
                            '<a class="fa fa-trash categoryItem cursor-pointer" title="Remove category"></a>' +
                            singleCategory.name + '</p></div>';
                $('#categoryContainerOverview-'+templateId).append(item);

                var node = '<option value="' + singleCategory.id + '">' + singleCategory.name + '</option>';
                $('#assignedCategory-'+templateId).append(node);
                $('#availableCategory-'+templateId).find("option[value='"+singleCategory.id+"']").remove();
            });
            $(".modal-body form").unblock();
        });
    });

    $('.removeCategoryShortcut').click(function(){
        if(confirm("Are you sure you want to DELETE this category subscription?"))
        {
            var id = $(this).closest('div').attr('id').split('-');
            console.log(id);
            var categoryId = id[1];
            var templateId = id[2];
            var subscription = {};
            var data = {};
            var category = [];

            subscription.id = categoryId;
            subscription.name = $('#' + categoryId).attr('value');

            category.push(subscription);

            data.categoryId = categoryId;
            data.advertiserId = $('#advertiserId').attr('value');
            data.templateId = templateId;
            data.companyId = $('#companyId').attr('value');

            $.ajax({
                type: 'POST',
                data: data,
                dataType: "json",
                url: '/chameleon/ajax/removeCategory.php'
            }).fail(function ()
            {
                $('#assigned-' + categoryId + '-' + templateId).empty().remove();
            });
        }
    });

    $('.removeCategoryOverview').click(function(e) {
        $(".modal-body form").block({
            message: '<h1>Removing categories</h1>',
            css: { border: '3px solid #a00' }
        });
        e.preventDefault();
        var id = $(this).attr('id').split('-');
        var templateId = id[1];
        var advertiserId = id[2];
        var data = {};
        var category = [];

        $('#assignedCategory-'+templateId).find('option:selected').each(function(i,selected){
            var subscription = {};
            subscription.id = $(selected).val();
            subscription.name = $.trim($(selected).text());
            category.push(subscription);
        });

        data.category = category;
        data.advertiserId = advertiserId;
        data.templateId   = templateId;
        data.companyId    = $('#companyId').attr('value');
        $.ajax({
            type: 'POST',
            data: data,
            dataType: "json",
            url: '/chameleon/ajax/removeCategory.php'
        }).done(function(){
        }).fail(function(){
            category.forEach(function(singleCategory){
                $("#assignedCategory-"+templateId).find("option[value='"+singleCategory.id+"']").remove();
                var node = '<option value="' + singleCategory.id + '">'+singleCategory.name+'</option>';
                $('#availableCategory-'+templateId).append(node);
                $('#assigned-'+singleCategory.id).empty().remove();

            });
            $(".modal-body form").unblock();
        });
    });

    $(".cloneTemplate").click(function(){
        var id = $(this).attr('id').split('-');
        var templateId = id[1];
        var advertiserId = id[2];
        var companyId = id[3];
        var data = {};

        if(confirm("Are you sure you want to CLONE this template? You will be redirected to the cloned template after confirming."))
        {
            data.advertiserId = advertiserId;
            data.templateId = templateId;

            $.ajax({
                type: 'POST',
                data: data,
                dataType: "json",
                url: '/chameleon/ajax/cloneTemplate.php',
                success: function (response)
                {
                    var url = window.location.origin + '/chameleon/index.php?page=editor&templateId=' + response.idBannerTemplate +
                        '&companyId=' + companyId + '&advertiserId=' + advertiserId;
                    window.location.replace(url);
                }
            });
        }
    });

    $(".deleteTemplate").click(function(){

        var id = $(this).attr('id').split('-');
        var templateId = id[1];
        var advertiserId = id[2];
        var companyId = id[3];
        var data = {};

        if(confirm("Are you sure you want to DELETE this template?"))
        {
            data.advertiserId = advertiserId;
            data.templateId   = templateId;

            $.ajax({
                type: 'POST',
                data: data,
                dataType: "html",
                url:  '/chameleon/ajax/deleteTemplate.php',
                success: function(response){
                    if(response.length === 0)
                    {
                        $('#template_'+templateId).fadeOut("slow", function(){
                            $(this).empty();
                        });
                    }
                }
            });
        }
    });
});
