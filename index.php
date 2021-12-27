<?php
require './vendor/autoload.php';
require './Visitor.php';
require './Function.php';
use PhpParser\Error;            # 异常输出类
use PhpParser\NodeDumper;       # 输出 抽象语法树 dump(parse后的对象)

use PhpParser\ParserFactory;    # 使用 create(ParserFactory::PREFER_PHP7) 方法创建一个解析器,然后使用 parser 对象的 parse 方法进行解析

use PhpParser\Node;             # 每个节点
use PhpParser\NodeTraverser;    # 节点转换器 addVisitor 增加转换器  traverse 遍历执行转换器
use PhpParser\NodeVisitorAbstract;  # 节点抽象类 extends ...
use PhpParser\NodeFinder;       # 节点查找器... findInstanceOf(节点, 节点类)

use PhpParser\PrettyPrinter\Standard as PrettyPrinter;    # 转换 PHP 代码用的 (prettyPrintFile)

ini_set('memory_limit','-1'); 
set_time_limit(0);

$firstFunction = 'BGGYeH';
$code = file_get_contents('./class.txt');

/*
$code = <<<'CODE'
<?php
namespace christmasTree {
    class WkcgU4gIU0 {
    
    public object $BIufXA;
    public object $gQ3wge63;
    
    public function fpxeqfFgd($dnpVQkek7w) {
        if (is_callable([$this->gQ3wge63, 'DtaGfFXx'])) @$this->gQ3wge63->DtaGfFXx($dnpVQkek7w);
		@$dnpVQkek7w = str_rot13($dnpVQkek7w);
		if (is_callable([$this->BIufXA, 'U9f9Hzh5L'])) @$this->BIufXA->U9f9Hzh5L($dnpVQkek7w);
    }
}
}
CODE;*/


$parser = (new ParserFactory()) -> create(1);
$stmts = $parser -> parse($code) [0]->stmts;
$PrettyPrinter = new PrettyPrinter();
$NodeTraverser = new NodeTraverser();

//去掉is_callable
$DropCallVisitor = new DelCallVisitor();
$NodeTraverser -> addVisitor($DropCallVisitor);
$stmts = $NodeTraverser -> traverse($stmts);
$NodeTraverser -> removeVisitor($DropCallVisitor);

/*
$dumper = new NodeDumper();
echo $dumper->dump($stmts);
echo $PrettyPrinter->prettyPrintFile($stmts);


$function = $stmts[0]->stmts[2];
var_dump(getCallLastName($function));
var_dump(getBase64Decode($function));
var_dump(getCallName($function));
var_dump(ParseExpression($function));
var_dump(getPos($function,'U9f9Hzh5L'));*/


//寻找pop链的过程
$pop = [];
findEval($stmts, $firstFunction);

var_dump($pop);

//  最后的一步，生成pop链
foreach ($pop as $pp){
    foreach ($pp as $key=>$p){
        $getClassVisitor = new ParseClassVisitor($p);
        $NodeTraverser -> addVisitor($getClassVisitor);
        $new_stmts = $NodeTraverser -> traverse($stmts);
        $NodeTraverser -> removeVisitor($getClassVisitor);
    }
}




