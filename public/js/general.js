function get_queue_stats()
{
    // Load Template
    var jqxhr = $.get("/api/get_queue_stats")
    .done(function(response) {
        $('#queuestats').html(response);
    });
}

$( document ).ready(function() 
{     
    get_queue_stats();
    setInterval(function(){
        get_queue_stats() // this will run after every 5 seconds
    }, 60000); 

    $('#queuestatsbutton').click(function(event)
    {
        event.preventDefault();
        get_queue_stats();
    });
});

