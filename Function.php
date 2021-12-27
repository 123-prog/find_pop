<?php
use PhpParser\Node;


# find eval...
function FindEval($stmts, $funcName, $string = ''){
    global $pop;
    global $firstFunction;
    foreach($stmts as $item){
        foreach($item -> stmts as $functions){
            if($functions instanceof Node\Stmt\ClassMethod){
                if($functions -> name -> name == $funcName){   //全局搜索目标方法
                    $resCall = getCallName($functions);
                    if($resCall){
                        $string .= $item -> name -> name .'---';  //此处获取了类名，并记录
                        echo $item -> name -> name."\r\n";
                        foreach($resCall as $CallName){  //这里可能包含触发invoke的情况
                            if($CallName != $firstFunction){
                                if(getPos($functions,$CallName)){  //语句在首个位置，不用过滤
                                    FindEval($stmts, $CallName, $string);   //寻找下一个类
                                }else{
                                    if(ParseExpression($functions)){  //这种情况必须过滤污染的影响
                                        FindEval($stmts, $CallName, $string);   //寻找下一个类
                                    }
                                }
                            }
                        }
                    }else{
                        $string .= $item -> name -> name;  //类中无调用方法，递归终点（
                        echo $item -> name -> name."\r\n";
                        $pop[] = explode('---', $string);  //将链拆开塞进pop数组
                    }
                }else if(getCallLastName($functions) == $funcName) {   //__call是万能的下一个跳板，但这里限制了条件，必须符合这个条件
                    //这种情况不存在污染
                    $resCall = getCallName($functions);
                    if($resCall){
                        $string .= $item -> name -> name .'---';  //此处获取了类名，并记录
                        echo $item -> name -> name."\r\n";
                        foreach($resCall as $CallName){
                            if($CallName != $firstFunction){
                                FindEval($stmts, $CallName, $string);   //寻找下一个类
                            }
                        }
                    }
                }else if(substr($funcName,0,8) == '__invoke'&&
                getBase64Decode($functions) == substr($funcName,9,strlen($funcName)-9) //下一个目标是__invoke型的类
                ){
                    //这种情况也不存在污染
                    $resCall = getCallName($functions);
                    if($resCall){
                        $string .= $item -> name -> name .'---'; //此处获取了类名，并记录
                        echo $item -> name -> name."\r\n";
                        foreach($resCall as $CallName){
                            if($CallName != $firstFunction){
                                FindEval($stmts, $CallName, $string);   //寻找下一个类
                            }
                        }
                    }
                }
            }
        }
    }
    
}

function getCallLastName($stmts){  //用于获取__call对应的上一个方法，判断下一次是不是可以跳到call来
    if( $stmts->name->name == '__call' &&
        $stmts->stmts[1] instanceof Node\stmt\Expression&&
        $stmts->stmts[1]->expr instanceof Node\Expr\FuncCall&&
        $stmts->stmts[1]->expr->name->parts[0] == 'call_user_func'
    ){
        $name = $stmts->stmts[1]->expr->args[0]->value->items[1]->value->name;
        return $name;
    }
    else{
        return false;
    }
}

function getBase64Decode($stmts){
    if($stmts->name->name == '__invoke'&&
    $stmts->stmts[0] instanceof Node\stmt\Expression&&
    $stmts->stmts[0]->expr->expr instanceof Node\Expr\FuncCall&&
    $stmts->stmts[0]->expr->expr->name->parts[0] == 'base64_decode'
    ){
        $name = $stmts->stmts[0] ->expr->expr->args[0]->value->value;
        return base64_decode($name);
    }
    else{
        return false;
    }
}

function getPos($stmts,$functionName){  //获取语句在类方法中的位置，在第一句就返回true,其他都是false,false必须经过变量消毒，以便过滤污染的影响
    $expression = $stmts->stmts[0];  //首个表达式
    if($expression->expr instanceof Node\Expr\ErrorSuppress&&
    $expression->expr->expr instanceof Node\Expr\MethodCall&&
    $expression->expr->expr->var instanceof Node\Expr\PropertyFetch&&
    $expression->expr->expr->var->var instanceof Node\Expr\Variable&&
    $expression->expr->expr->name->name == $functionName
    ){
        return true;
    }else{
        return false;
    }
}

# 变量消毒
function ParseExpression($stmts){
    foreach($stmts -> stmts as $expression){
        if($expression instanceof Node\Stmt\Expression&&
        $expression->expr instanceof Node\Expr\ErrorSuppress&&
        $expression->expr->expr instanceof Node\Expr\Assign&&
        $expression->expr->expr->var instanceof Node\Expr\Variable&&
        $expression->expr->expr->expr instanceof Node\Expr\FuncCall
        ){
            $functionName = $expression->expr->expr->expr->name->parts[0];
            if($functionName!='crypt'&&$functionName!='sha1'&&$functionName!='md5'){
                return true;
            }else{
                return false;
            }
        }else if($expression instanceof Node\Stmt\Expression&&
        $expression->expr instanceof Node\Expr\ErrorSuppress&&
        $expression->expr->expr instanceof Node\Expr\Assign&&
        $expression->expr->expr->var instanceof Node\Expr\Variable&&
        $expression->expr->expr->expr instanceof Node\Expr\Variable
        ){
            $name1 = $expression->expr->expr->var->name;
            $name2 = $expression->expr->expr->expr->name;
            if($name1 == $name2){
                return true;
            }else{
                return false;
            }
        }
    }
    return true;
}

# 提取调用方法  //需要更改，新增__invoke和__call这两种跳板的情况
function getCallName($stmts){   //返回的是对应ClassMethod内部调用的方法集合，用于寻找下一个类
    $call = array();
    if($stmts->name->name == '__call'){
        //call跳板的情况,要获取到下一个调用方法，extract的参数就是
        foreach($stmts -> stmts as $stmt){
            if($stmt instanceof Node\Stmt\Expression &&
            $stmt -> expr instanceof Node\Expr\FuncCall &&
            $stmt -> expr -> name -> parts[0] == 'extract'
            ){
                $call[] = $stmt -> expr -> args[0] -> value -> items[0]-> value-> value;
            }
        }
    }else{
        //正常的跳板
        foreach($stmts -> stmts as $stmt){
            if($stmt instanceof Node\Stmt\Expression &&
            $stmt -> expr -> expr instanceof Node\Expr\MethodCall &&
            $stmt -> expr -> expr -> var instanceof Node\Expr\PropertyFetch
            ){
                $call[] = $stmt -> expr -> expr -> name -> name;   //获取下一步寻找的方法
            }
            else if($stmt instanceof Node\Stmt\Expression&&
                $stmt -> expr ->expr instanceof Node\Expr\FuncCall&&
                $stmt -> expr ->expr ->name ->parts[0] == 'call_user_func'
                ){
                    $key = $stmt -> expr -> expr ->args[1]->value->items[0]->key->value;
                    $call[] = '__invoke#'.$key;    //可以触发invoke, 所以添加__invoke方法，寻找下一个类,这里耍点小聪明，把参数值带上，之后判断用哪个类的__invoke就很方便了
                }
        }
    }
    return $call;
}



