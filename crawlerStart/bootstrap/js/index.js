/**
 * Created by apple on 16/3/11.
 */
$(document).ready(function(){
    $('.startCrawler').click(function(){
        startCrawler();
    });

    $('.num').live('mouseover', function(){
        $(this).bumpyText();
    });
});

function startCrawler(){
    $.ajax(url, content, function(t){
        if(t == 'success'){
            window.location.reload();
        }else{
            alert("开启失败");
        }
    });
}

function getAllSources(){
    var url = './index.php?type=getAllSource';
    $.post(url, {}, function(t){
        if(t.status){
            $.each(t, function(n, value){
                if(n != 'status'){
                    if($('.'+n).html() <= 0 || $('.'+n).html == ''){
                        if(value.num > 0){
                            window.location.reload();
                        }
                    }
                    if($('.'+n+'illega').html() <= 0){
                        if(value.illeganum >= 1){
                            window.location.reload();
                        }
                    }
                    if($('.'+n).text() != value.num && value.num != ''){
                        $('.'+n).css('color', '#3DA600');

                        $('.'+n).text(value.num);
                    }
                    if($('.'+n+'illega').text() != value.illeganum && value.illeganum != ''){
                        $('.'+n+'illega').css('color', '#3DA600');

                        $('.'+n+'illega').text(value.illeganum);
                    }
                    if($('.'+n+'seed').text() != value.seed && value.seed != ''){
                        $('.'+n+'seed').css('color', '#3DA600');

                        $('.'+n+'seed').text(value.seed);
                    }
                    if($('.'+n+'etlnum').text() != value.etlnum && value.etlnum != ''){
                        $('.'+n+'etlnum').css('color', '#3DA600');

                        $('.'+n+'etlnum').text(value.etlnum);
                    }
                }
            });
            setTimeout("setcolorsoru()", 3000);
        }
    }, 'json');
}

function setcolorsoru(){
    $('tr td').css('color', '#333');
}

setInterval("getAllSources()", 6000);