<?php

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\NodeVisitorAbstract;  # 节点抽象类 extends ...

// # 删除 method_exists
// class DelCallVisitor extends NodeVisitorAbstract {
//     public function leaveNode(Node $node)
//     {
//         if($node instanceof Node\Stmt\If_ &&
//         $node -> cond instanceof FuncCall &&
//         $node -> cond -> name -> parts[0] == 'method_exists'
//         ){
//             if($node -> stmts){
//                 return $node -> stmts[0];
//             }
//         }
//     }
// }
//删除is_callable
class DelCallVisitor extends NodeVisitorAbstract{
    public function leaveNode(Node $node)
    {
        if($node instanceof Node\Stmt\If_&&
        $node->cond instanceof FuncCall&&
        $node->cond->name->parts[0] == 'is_callable'
        ){
            if($node -> stmts){
               return $node -> stmts[0];
            }
        }
    }
}

# Add Construct ...
class ParseClassVisitor extends NodeVisitorAbstract {
    public $className;
    
    public function __construct($className){
        $this -> className = $className;
    }

    public function leaveNode(Node $node)
    {
        if($node instanceof Node\Stmt\Class_&&$node->name->name == $this->className){
            global $PrettyPrinter;
            $body = $PrettyPrinter->prettyPrintFile($node->stmts);
            $content = 'class '.$this->className."{\n".substr($body,5,strlen($body)-5)."\n}\n";
            file_put_contents('poc.php',$content,FILE_APPEND); 
        }
    }
}




