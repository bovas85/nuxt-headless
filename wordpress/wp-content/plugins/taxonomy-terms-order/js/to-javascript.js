
    function to_change_taxonomy(element)
        {
            //select the default category (0)
            jQuery('#to_form #cat').val(jQuery("#to_form #cat option:first").val());
            jQuery('#to_form').submit();
        }
        
    var convArrToObj = function(array){
                            var thisEleObj = new Object();
                            if(typeof array == "object"){
                                for(var i in array){
                                    var thisEle = convArrToObj(array[i]);
                                    thisEleObj[i] = thisEle;
                                }
                            }else {
                                thisEleObj = array;
                            }
                            return thisEleObj;
                        }