
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
