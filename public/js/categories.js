
$( document ).ready(function() 
{   
    $("a.refreshLink").each(function( ) {      

        $(this).click( function(event) {

            event.preventDefault();

            // Get Suggestions

            $.get( $(this).attr('href'))
            .done(function(suggestions_data) {
                
                console.log('Successfully received suggestions. Continuing to load template.');

                // Load Template
                var jqxhr = $.post( "/api/load_template", { 
                    template_name: "modal", 
                    title: "Choose the category you want to link to", 
                    suggestions: suggestions_data
                })
                .done(function(response) {
                    console.log( "HTTP Request: success" );
                    
                    if (response.error == undefined)
                    {
                        console.log( "This is the result fucker: " + response.data);

                        $('#CategoriesView').append(response.data);

                        $( "#dialog" ).dialog({
                            modal: true,
                        });
                    }
                    else
                    {
                        console.log( "Error: " + response.error);
                    }
                })
                .fail(function() {
                    console.log( "HTTP Request: error" );
                })
            });

            


        });
    });

});