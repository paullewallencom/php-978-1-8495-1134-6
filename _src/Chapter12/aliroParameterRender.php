<?php

/*******************************************************************************
 * Aliro - the modern, accessible content management system
 *
 * This code is copyright (c) Aliro Software Ltd - please see the notice in the
 * index.php file for full details or visit http://aliro.org/copyright
 *
 * Some parts of Aliro are developed from other open source code, and for more
 * information on this, please see the index.php file or visit
 * http://aliro.org/credits
 *
 * Author: Martin Brampton
 * counterpoint@aliro.org
 *
 * aliroParameterRender is the class that provides HTML for a set of parameters
 * that have been defined with XML and stored in an aliroParameters object.
 *
 * Each type of parameter has a subclass of aliroParameterRender.  For the
 * parameter type "text" the name of the class must be aliroParameterRenderText.
 *
 */

abstract class aliroParameterRender {
	// Static properties, used in the static method and the constructor
	protected static $paramcount = 0;
	protected static $html = array();
	// The following are the main attributes from a single <param> element
	// Only those that are present will be non-null
	protected $type = '';
	protected $name = '';
	protected $label = '';
	protected $default = '';
	protected $description = '';
	protected $size = '';
	protected $rows = '';
	protected $cols = '';
	protected $class = '';
	protected $method = '';
	protected $directory = '';
	// This is the current value of the parameter, taken from the parameter object
	protected $value = '';
	// An instance of aliroHTML is provided for convenience
	protected $alirohtml = null;
	// This is the complete associative array representing the attributes
	// of the <param> element
	protected $param = array();

	// The constructor sets things up ready for a specific parameter renderer
	protected function __construct ($param, $poptions, $pobject, $controlname) {
		$this->param = $param;
		$this->alirohtml = aliroHTML::getInstance();
		foreach (array('type', 'name', 'label', 'default', 'description', 'size', 'rows', 'cols', 'class', 'method', 'directory') as $item) {
			$this->$item = isset($param[$item]) ? $param[$item] : '';
		}
		$tooltip = $this->description ? $this->alirohtml->toolTip($this->description, $this->name) : '';
		$this->value =  ($pobject instanceof aliroParameters) ? $pobject->get($this->name, $this->default) : $this->default;

		aliroParameterRender::$html[] = '<tr>';
		if ($this->label) {
			$this->label = ('@spacer' == $this->label) ? '<hr />' : $this->label.':';
		}
		aliroParameterRender::$html[] = '<td width="35%" align="right" valign="top">'.$this->label.'</td>';
		// This is where the specific parameter renderer is called, in the current subclass
		aliroParameterRender::$html[] = "<td>{$this->renderItem($poptions, $controlname)}</td>";
		aliroParameterRender::$html[] = "<td width='10%' align='left' valign='top'>$tooltip</td>";
		aliroParameterRender::$html[] = '</tr>';
		aliroParameterRender::$paramcount++;
	}

	abstract protected function renderItem ($poptions, $controlname);

	protected function T_ ($string) {
		return function_exists('T_') ? T_($string) : $string;
	}

	// This is the main renderer which analyses and displays a set of parameters
	// The parmspec is the array form of the <params> XML
	// The pobject is an aliroParameters object
	// The controlname is used as the name in the HTML for entering a value
	public static function renderParameters ($parmspec, $pobject, $controlname) {
		self::$html[] = '<table class="paramlist">';
		if (!empty($parmspec)) foreach ($parmspec as $aparam) {
			$options = isset($aparam['options']) ? $aparam['options'] : array();
			$param = $aparam['attribs'];
			if (isset($param['type'])) {
				$classname = 'aliroParameterRender'.ucfirst(strtolower($param['type']));
				if (class_exists($classname, false) OR aliro::getInstance()->classExists($classname)) {
					new $classname($param, $options, $pobject, $controlname);
					continue;
				}
			}
			new aliroParameterRenderInvalid($param, $options, $pobject, $controlname);
		}
		if (0 == self::$paramcount) {
			$message = function_exists('T_') ? T_('There are no Parameters for this item') : 'There are no Parameters for this item';
			return <<<NULL_HTML

				<table class="paramlist">
					<tr>
						<td colspan="2">
							<i>$message</i>
						</td>
					</tr>
				</table>

NULL_HTML;

		}
		self::$html[] = '</table>';
		$result = implode("\n", self::$html);
		self::$paramcount = 0;
		self::$html[] = array();
		return $result;
	}
}

// Each individual parameter renderer returns some appropriate HTML
class aliroParameterRenderInvalid extends aliroParameterRender {
	// The poptions array is the set of <option> elements within a <param>
	// The controlname should be used as the name for the input in the HTML
	protected function renderItem ($poptions, $controlname) {
	    return $this->T_('Handler not defined for type').'='.$this->type;
	}
}

class aliroParameterRenderText extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
	    return '<input type="text" name="'.$controlname.'['.$this->name.']" value="'.$this->value.'" class="text_area" size="'.$this->size.'" />';
	}
}

class aliroParameterRenderList extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
		$options = array();
		foreach ($poptions as $option) $options[] = $this->alirohtml->makeOption((string) $option['value'], (string) $option[0]);
	    return $this->alirohtml->selectList($options, $controlname.'['.$this->name.']', 'class="inputbox"', 'value', 'text', $this->value);
	}
}

class aliroParameterRenderRadio extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
		$options = array();
		foreach ($poptions as $option) $options[] = $this->alirohtml->makeOption((string) $option['value'], (string) $option[0]);
	    return $this->alirohtml->radioList($options, $controlname.'['.$this->name.']', '', $this->value);
	}
}

class aliroParameterRenderImagelist extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
	    $dir = new aliroDirectory (_ALIRO_ABSOLUTE_PATH.$this->directory);
	    $files = $dir->listFiles ('\.png$|\.gif$|\.jpg$|\.bmp$|\.ico$');
	    $options = array();
	    foreach ($files as $file) $options[] = $this->alirohtml->makeOption($file, $file);
	    if (!isset($this->param['hide_none'])) array_unshift($options, $this->alirohtml->makeOption('-1', '- Do not use an image -' ));
	    if (!isset($this->param['hide_default'])) array_unshift($options, $this->alirohtml->makeOption('', '- Use Default image -'));
	    return $this->alirohtml->selectList ($options, "{$controlname}[{$this->name}]", 'class="inputbox"', 'value', 'text', $this->value);
	}
}

class aliroParameterRenderTextarea extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
        $value = str_replace ('<br /', "\n", $this->value);
        return "<textarea name='params[$this->name]' cols='$this->cols' rows='$this->rows' class='text_area'>$value</textarea>";
	}
}

class aliroParameterRenderEditarea extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
		$editor = aliroEditor::getInstance();
		return $editor->editorAreaText( $controlname.'['.$this->name.']',  $this->value , $controlname.'['.$this->name.']', '700', '350', '95', '30' ) ;
	}
}

class aliroParameterRenderDynamic extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
		$object = aliroRequest::getInstance()->getClassObject($this->class);
		if (is_object($object) AND method_exists($object, $this->method)) {
			return $object->$method($this->name, $this->value, $controlname, $this->param);
		}
		else return sprintf($this->T_('Dynamic parameter class: %s, method: %s failed'), $this->class, $this->method);
	}
}

class aliroParameterRenderSpacer extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
		return $this->value ? $this->value : '<hr />';
	}
}

class aliroParameterRenderMos_Section extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
		$database = aliroDatabase::getInstance();
		$query = "SELECT id AS value, title AS text"
		. "\n FROM #__sections"
		. "\n WHERE published='1' AND scope='content'"
		. "\n ORDER BY title"
		;
		$database->setQuery( $query );
		$options = (array) $database->loadObjectList();
		array_unshift($options, $this->alirohtml->makeOption( '0', '- Select Content Section -' ));
		return $this->alirohtml->selectList( $options, $controlname .'['. $this->name .']', 'class="inputbox"', 'value', 'text', $this->value );
	}
}

class aliroParameterRenderMos_category extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
		$firstoption = $this->alirohtml->makeOption('0', '- Select Content Category -');
		$database = aliroDatabase::getInstance();
		$query 	= "SELECT c.id AS value, CONCAT_WS( '/',s.title, c.title ) AS text"
		. "\n FROM #__categories AS c"
		. "\n LEFT JOIN #__sections AS s ON s.id=c.section"
		. "\n WHERE c.published='1' AND s.scope='content'"
		. "\n ORDER BY c.title"
		;
		$database->setQuery( $query );
		$options = (array) $database->loadObjectList();
		array_unshift($options, $firstoption);
		return $this->alirohtml->selectList( $options, $controlname .'['. $this->name .']', 'class="inputbox"', 'value', 'text', $this->value );
	}
}

class aliroParameterRenderMos_menu extends aliroParameterRender {
	protected function renderItem ($poptions, $controlname) {
		$handler = aliroMenuHandler::getInstance();
		$menuTypes = $handler->getMenutypes();
		$options[] = $this->alirohtml->makeOption( '', '- Select Menu -' );
		foreach($menuTypes as $menutype) $options[] = $this->alirohtml->makeOption($menutype, $menutype);
		return $this->alirohtml->selectList( $options,  $controlname .'['. $this->name .']', 'class="inputbox"', 'value', 'text', $this->value );
	}
}