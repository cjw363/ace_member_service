<?php

class TrantorException extends ErrorException {

	private $type;

	const TYPE_1_PARAMETER_ERROR =  1; //参数错误
	const TYPE_2_NORMAL = 2; //直接返回
	const TYPE_3_I18N = 3; // 需要返回语言包信息

	public function __construct($message='', $type = 1, $code = 0) {
		$this->type = $type;
		parent::__construct($message, $code);
	}

	public function __toString() {
		$isDev = getConf('flag_dev');
		if(!$isDev) return '[ FATAL ERROR ]';

		$str = '';
		$traceArr = $this->getTrace();
		foreach ($traceArr as $trace) {
			$class = $trace['class'];
			$fn = $trace['function'];
			if ($class) {
				$str .= $class;
			}
			if ($fn) {
				if ($str) {
					$str .= '::';
				}
				$str .= $fn;
				break;
			}
		}
		if($this->type ==  self::TYPE_1_PARAMETER_ERROR){
			if($this->message){
				$str .= ' Parameter Error (' . $this->message . ')<br><br>';
			}else{
				$str .= ' Parameter Error <br><br>';
			}
		}else if($this->type == self::TYPE_2_NORMAL){
			$str .= ' ' . $this->message . '<br><br>';
		}else if($this->type == self::TYPE_3_I18N){
			$str .= ' ' . i18n($this->message) . '<br><br>';
		}
		$str.= '<div style="text-align: left;">' . $this->_getExceptionTraceAsString($traceArr) . '</div>';
		return $str;
	}

	private function _getExceptionTraceAsString($traceArr) {
		$rtn = "";
		$count = 0;
		foreach ($traceArr as $trace) {
			$args = "";
			if (isset($trace['args'])) {
				$args = array();
				foreach ($trace['args'] as $arg) {
					if (is_string($arg)) {
						$args[] = "'" . $arg . "'";
					} elseif (is_array($arg)) {
						$args[] = "Array";
					} elseif (is_null($arg)) {
						$args[] = 'NULL';
					} elseif (is_bool($arg)) {
						$args[] = ($arg) ? "true" : "false";
					} elseif (is_object($arg)) {
						$args[] = get_class($arg);
					} elseif (is_resource($arg)) {
						$args[] = get_resource_type($arg);
					} else {
						$args[] = $arg;
					}
				}
				$args = join(", ", $args);
			}
			$file = "[internal function]";
			$classFn = $trace['class'];
			$fn = $trace['function'];
			if($classFn){
				if($fn) $classFn .= '::'.$fn;
			}else if($fn){
				$classFn = $fn;
			}
			if (isset($trace['file'])) {
				$file = $trace['file'];
				$line = "";
				if (isset($trace['line'])) {
					$line = $trace['line'];
				}
				$rtn .= sprintf("#%s %s(%s): %s(%s)\n", $count, $file, $line, $classFn, $args) . '<br><br>';
			}else{
				$rtn .= sprintf("#%s %s: %s(%s)\n", $count, $file, $classFn, $args) . '<br><br>';
			}
			$count++;
		}
		return $rtn;
	}

}
