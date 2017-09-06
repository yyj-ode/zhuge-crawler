<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>爬虫管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="./bootstrap/css/bootstrap.mins.css" rel="stylesheet" media="screen">
    <link href="./bootstrap/img/spider.jpg" rel="shortcut icon">
</head>
<body style="background:url(./bootstrap/img/spider.jpg) no-repeat center fixed;background-size:contain">
<style>
    .bumpy-char {
        /*line-height: 3.4em;*/
        position: relative;
    }
</style>
<h1></h1>
    <!--<img with="200" height="200" src="./bootstrap/img/spider.jpg"/>-->
    <script src="./bootstrap/js/jquery.js"></script>
    <script type="text/javascript" src="./bootstrap/js/jquery.min.js"></script>
    <script type="text/javascript" src="./bootstrap/js/jquery.bumpytext.packed.js"></script>
    <script type="text/javascript" src="./bootstrap/js/easying.js"></script>
    <script src="./bootstrap/js/bootstrap.min.js"></script>
    <script src="./bootstrap/js/index.js"></script>
<!--<form class="form-horizontal" role="form">-->
<!--    <fieldset>-->
<!--        <legend>配置抓取规则</legend>-->
<!--        <div class="form-group">-->
<!--            <label class="col-sm-2 control-label">爬取器</label>-->
<!--            <div class="col-sm-4">-->
<!--                每 <div class="btn-group">-->
<!--                    <button type="button" class="btn btn-default dropdown-toggle"-->
<!--                            data-toggle="dropdown">-->
<!--                        默认 <span class="caret"></span>-->
<!--                    </button>-->
<!--                    <ul class="dropdown-menu" role="menu">-->
<!--                        <li>1天</li>-->
<!--                        <li>2天</li>-->
<!--                        <li>3天</li>-->
<!--                        <li>4天</li>-->
<!--                    </ul>-->
<!--                </div>一次-->
<!--            </div>-->
<!--        </div>-->
<!--    </fieldset>-->
<!--</form>-->

<!--<table class="table">-->
<!--    <caption>爬取器</caption>-->
<!--    <thead>-->
<!--        <tr>-->
<!--            <th>ip</th>-->
<!--<!--            <th>状态</th>-->-->
<!--        </tr>-->
<!--    </thead>-->
<!--    <tbody>-->
<!--        --><?php //foreach((array)$serverips['Grab-server'] as $key => $value){ ?>
<!--            <tr class="success">-->
<!--                <td>--><?php //echo $value?><!--</td>-->
<!--<!--                <td>10/11/2013</td>-->-->
<!--            </tr>-->
<!--        --><?php //}?>
<!--    </tbody>-->
<!--</table>-->
<!---->
<!--<table class="table">-->
<!--    <caption>抓取器</caption>-->
<!--    <thead>-->
<!--    <tr>-->
<!--        <th>ip</th>-->
<!--<!--        <th>状态</th>-->-->
<!--    </tr>-->
<!--    </thead>-->
<!--    <tbody>-->
<!--    --><?php //foreach((array)$serverips['Crawling-server'] as $key => $value){ ?>
<!--        <tr class="success">-->
<!--            <td>--><?php //echo $value?><!--</td>-->
<!--<!--            <td>10/11/2013</td>-->-->
<!--        </tr>-->
<!--    --><?php //}?>
<!--    </tbody>-->
<!--</table>-->

<!--<table class="table">-->
<!--    <caption>Etl</caption>-->
<!--    <thead>-->
<!--    <tr>-->
<!--        <th>服务器</th>-->
<!--        <th>状态</th>-->
<!--    </tr>-->
<!--    </thead>-->
<!--    <tbody>-->
<!--    --><?php //foreach((array)$serverips['Etl-server'] as $key => $value){ ?>
<!--        <tr class="active">-->
<!--            <td>--><?php //echo $value?><!--</td>-->
<!--            <td>10/11/2013</td>-->
<!--        </tr>-->
<!--    --><?php //}?>
<!--    </tbody>-->
<!--</table>-->

    <table class="table">
        <caption>渠道列表</caption>
<!--        <button type="button" class="btn btn-success startCrawler">开始抓取</button>-->
        <thead>
            <tr>
                <th>渠道名称</th>
                <th>官网数</th>
                <th>种子</th>
                <th>不合法种子</th>
                <th>抓取内容</th>
                <th>ETL</th>
                <th>状态</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
        <?php
            $i = 0;
            foreach((array)$source as $key => $value){?>
            <?php
                $class = '';
                if($value['num'] >= 1 || $value['seed'] >= 1){
                    $class = 'success';
                }
                if($value['illega'] >= 1){
                    $class = 'danger';
                }
            ?>
            <tr class="<?php echo 'crawlercls'.$value['source'];?> <?php echo $class?>">
                <td><div class="num"><?php echo $value['classname']?></div><?php echo' '.getSourceType($value['source'])?></td>
                <!--<td><div class="num"><?php // echo $housenums[$value['source']]?></div></td>-->
                <td><div class="num"><?php echo $value['count'];?></div></td>
                <td class="num <?php echo $value['source'].'seed';?>"><?php echo $value['seed'] == '' ? 0 : $value['seed'];?></td>
                <td class="num <?php echo $value['source'].'illega';?>"><?php echo $value['illega'] == '' ? 0 : $value['illega'];?></td>
                <td tag="<?php echo $value['source'];?>" class="num sourcenum <?php echo $value['source'];?>"><?php echo $value['num'] == '' ? 0 : $value['num'];?></td>
                <td tag="<?php echo $value['source'].'etlnum';?>" class="num etlnum <?php echo $value['source'].'etlnum';?>"><?php echo $value['etlnum'] == '' ? 0 : $value['etlnum'];?></td>
                <td class="num">正在爬取</td>
                <td>
                    <?php if($i != 0){ ?>
                        <a href="./index.php?type=ding&source=<?php echo $value['source']?>">
                            <button type="button" class="btn btn-success">顶</button>
                        </a>
                    <?php }?>
                    <a href="./test.php?file=<?php echo $value['source']?>" target="_blank">
                        <button type="button" class="btn btn-primary">检测</button>
                    </a>
                    <a href="./index.php?type=newding&source=<?php echo $value['source']?>">
                        <button type="button" class="btn btn-success">重新抓取</button>
                    </a>
                </td>
            </tr>
        <?php
            $i++;
            }?>
        <!--    <tr class="active">-->
        <!--        <td>产品2</td>-->
        <!--        <td>10/11/2013</td>-->
        <!--        <td>发货中</td>-->
        <!--    </tr>-->
    <!--    <tr class="success">-->
    <!--        <td>产品2</td>-->
    <!--        <td>10/11/2013</td>-->
    <!--        <td>发货中</td>-->
    <!--    </tr>-->
    <!--    <tr  class="warning">-->
    <!--        <td>产品3</td>-->
    <!--        <td>20/10/2013</td>-->
    <!--        <td>待确认</td>-->
    <!--    </tr>-->
    <!--    <tr  class="danger">-->
    <!--        <td>产品4</td>-->
    <!--        <td>20/10/2013</td>-->
    <!--        <td>已退货</td>-->
    <!--    </tr>-->
        </tbody>
    </table>
</body>
</html>

<?php

function checkDataSource($source = '', $type = ''){
    if(!empty($source) && !empty($type)){
        return isExistsStr(strtolower($source), strtolower($type));
    }
    return false;
}

function getSourceType($source){
    if(checkDataSource($source, 'rent')){ //整租
        return '<b style="color: #FEC42F">整租</b>';
    }elseif(checkDataSource($source, 'hezu')) { //合租
        return '<b style="color: #FC6230">合租</b>';
    }else{ //二手房
        return '<b style="color: #44CCEA">二手房</b>';
    }
}



