
$('.delete_form').submit(function() {
    return confirm('Are you sure you want to do this action ?');
});

/**
 * Fire 2 queries when the preview modal (called "Test parsing") opens.
 * It gives ability to preview the content with both parser
 */
$('#modal-test-item').on('opened', function () {
    // we need to reflow the sections to get tabs to display properly
    $(this).foundation('section', 'reflow');

    var fetchUrl = $('#modal-content-internal').data('fetch-url');

    callParser(
        $('#modal-content-internal'),
        fetchUrl,
        'internal'
    );

    callParser(
        $('#modal-content-external'),
        fetchUrl,
        'external'
    );
});

/**
 * Call the backend parser to fetch content
 *
 * @param  {dom id} context Where to write html
 * @param  {string} link    Url for the call
 * @param  {string} parser  Parser type: internal / external
 */
function callParser (context, link, parser) {
    $.ajax({
        type: 'GET',
        url: link,
        data: {
            parser: parser
        },
        dataType: 'html',
        timeout: 10000,
        context: context,
        success: function(data){
            this.html(data);
        },
        error: function(xhr, type){
            alert('Ajax error!');
        }
    });
}

$(function(){
    $('.time-ago').each(function() {
        var time = $(this);
        time.text(moment(time.attr('title')).fromNow());
    });

    // hide textarea for config file when it's empty
    var siteconfig = $('div.siteconfig');
    var siteconfigTextarea = $('div.siteconfig').find('textarea');

    if (siteconfigTextarea.length && siteconfigTextarea.val().trim() == '') {
        siteconfigTextarea.hide();
    }

    // display textarea siteconfig on label click, but don't toggle when it's not empty
    $(document).on('click', siteconfig.find('.try-siteconfig'), function (e) {
        if (siteconfigTextarea.val().trim() != '') {
            return false;
        }

        siteconfigTextarea.toggle();
        return false;
    })

    var outputLog = $('div.output-log');
    outputLog.hide();

    // display textarea siteconfig on label click, but don't toggle when it's not empty
    $(document).on('click', $('.view-debug'), function (e) {
        outputLog.toggle();

        return false;
    })
});
