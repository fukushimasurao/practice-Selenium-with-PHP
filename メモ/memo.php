

- routes/web.php
Route::any('/lazada_search/{id?}', ['as' => '.lazada_search', 'uses' => 'CurationExternalController@lazada_search']);


- app/Http/Controllers/Admin/CurationExternalController.php

public function lazada_search(Request $request, $id = null)
{
    $response = app('ServiceWebSearch')->lazada_search($request->input('request', ''));

    return response()->json($response);
}


resources/views/admin/curation/elements/js.blade.php

$("#lazada-search").click(function(){ //画像で検索
        var data = {request:$("#lazada-search-form").val()};

        $("#lazada-search-loader").attr('hidden', false);
        $("#lazada-search-result").attr('search-word', $("#lazada-search-form").val());

        lazada_api(data, function(data, textStatus){
            $("#lazada-search-result").html("");
            $('#lazada-search-result').animate({ scrollTop: 0 }, 'fast');
            for (var i=0 ; i<data["results"].length ; i++){
                var lazada_result=data["results"][i];
                console.log(lazada_result);
                var lazada_url = lazada_result["scrape_url"];
                var lazada_imageUrl = lazada_result["scrape_img"];
                var lazada_title = lazada_result["scrape_title"];
                $("#lazada-search-result").append('<div class="item_list_content_lazada clearfix"><p>'+(i+1)+'件目'+'</p><img src="'+lazada_imageUrl+'"><ul><li class="item_list_title">'+lazada_title+'</li><li class="item_list_link">'+lazada_url+'</li><li class="item_list_image_url">'+lazada_imageUrl+'</li></ul><div class="add_btn"><a class="btn btn-default" onclick="">追加する</a></div></div>');
            }
            $("#lazada-search-loader").attr('hidden', true);
        });
    });


    function lazada_api(data, callback) {
        ajax({
            url:"/admin/curation/external/lazada_search",
            data: data,
            dataType: "xml",
            timeout: 10000,
            error:function (XMLHttpRequest, textStatus, errorThrown) {
                alert("違う単語で検索してみてください");
            },
            success: callback
        });
        return false;
    }


    resources/views/admin/curation/elements/modal/editImageItemModal.blade.php

    <li class="btn" style="display: block;">公式写真</li>
                            <li class="btn" style="display: block;">URLから追加</li>
                            <li class="btn" style="display: block;">Amazonで検索</li>
                            <li class="btn" style="display: block;">楽天で検索</li>
                            <li class="btn" style="display: block;">Yahoo!ショッピングで検索</li>
                            <li class="btn" style="display: block;">lazadaで検索</li>
                            <?php if (in_array( $authUser['role'], array('admin')) ){ ?>
                            <li class="btn" style="display: block;">ブックマークレットから追加</li>
                            <li class="btn" style="display: block;">画像検索</li>
                            <li class="btn" style="display: block;">アップロード</li>

    resources/views/admin/curation/elements/modal/editImageModal.blade.php


<div class="modal fade" id="lazadaModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">lazadaで検索して追加</h4>
            </div>
            <div class="modal-body">
                <form role="form">
                    <div class="form-group">
                        <input id="lazada-search-form" type="text" class="form-control" placeholder="検索するワードを入力" style="width:400px; display:inline-block;">
                        <button id="lazada-search" type="button" class="btn btn-primary">検索</button>
                        <img id="lazada-search-loader" src="https://image.knowsia.jp/common/loading.gif" hidden="hidden">
                    </div>
                </form>
                <div id="lazada-search-result">
                </div>
            </div>
        </div>
    </div>
</div>