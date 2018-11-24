

    var getUrlParameter = function getUrlParameter(sParam) 
        {
            var sPageURL = decodeURIComponent(window.location.search.substring(1)),
                sURLVariables = sPageURL.split('&'),
                sParameterName,
                i;

            for (i = 0; i < sURLVariables.length; i++) {
                sParameterName = sURLVariables[i].split('=');

                if (sParameterName[0] === sParam) {
                    return sParameterName[1] === undefined ? true : sParameterName[1];
                }
            }
        };

    jQuery(document).ready(function()
        {
            
            jQuery('table.posts #the-list').sortable({
                                                        'items': 'tr',
                                                        'axis': 'y',
                                                        'update' : function(e, ui) {
                                                           
                                                            var post_type           =   jQuery('input[name="post_type"]').val();
                                                            var order               =   jQuery('#the-list').sortable('serialize');
                                                            
                                                            var paged       =   getUrlParameter('paged');
                                                            if(typeof paged === 'undefined')
                                                                paged   =   1;
                                                            
                                                            var queryString = { "action": "update-custom-type-order-archive", "post_type" : post_type, "order" : order ,"paged": paged, "archive_sort_nonce"    :   CPTO.archive_sort_nonce};
                                                            //send the data through ajax
                                                            jQuery.ajax({
                                                              type: 'POST',
                                                              url: ajaxurl,
                                                              data: queryString,
                                                              cache: false,
                                                              dataType: "html",
                                                              success: function(data){
                                                
                                                              },
                                                              error: function(html){

                                                                  }
                                                            });
                                                        
                                                        }
                                                    });
       

    });
