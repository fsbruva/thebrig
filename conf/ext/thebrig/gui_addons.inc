<?php
/* 
    gui_addons.inc

	Copyright (c) 2015 Matt Kempe & Alexey Kruglov
	All rights reserved. 

	Part of NAS4Free (http://www.nas4free.org).
	Copyright (c) 2012-2015 The NAS4Free Project <info@nas4free.org>.
	All rights reserved.

	Portions of freenas (http://www.freenas.org).
	Copyright (c) 2005-2011 by Olivier Cochard <olivier@freenas.org>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer.
	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies,
	either expressed or implied, of the NAS4Free Project.

This file content specific subroutines for Thebrig gui.
I use it differet from functions because it need for gui only, but functions we use from cli
No any comments, because I use perfect NAS4Free constructor in file /etc/inc/wui.inc
*/

class HTMLBrigCommandBox extends HTMLBaseControl {
	

	function __construct($ctrlname, $title, $value, $description = "") {
		parent::__construct($ctrlname, $title, $value, $description);
	}

	function RenderCtrl() {
		$ctrlname = $this->GetCtrlName();
		$value = $this->GetValue();
		

		echo "    <script type='text/javascript'>\n";
		echo "    //<![CDATA[\n";
		echo "    function onchange_{$ctrlname}() {\n";
		echo "      var value1 = document.getElementById('{$ctrlname}');\n";
		echo "      var result = value1.value.split('|');\n";
		echo "      document.getElementById('{$ctrlname}data').value = result[2];\n";
		echo "      document.getElementById('{$ctrlname}filetype').value = result[0];\n";
		echo "      document.getElementById('{$ctrlname}datanice').value = result[1];\n";
		echo "    }\n";
		echo "    function onclick_add_{$ctrlname}() {\n";
		echo "      var value1 = document.getElementById('{$ctrlname}data').value;\n";
		echo "      var valuetype = document.getElementById('{$ctrlname}filetype').value;\n";
		echo "      var valuenice = document.getElementById('{$ctrlname}datanice').value;\n";
		echo "      if (value1 != '' ) {\n";
		echo "      var value = valuetype + '|' + valuenice + '|' + value1;\n";
		echo "          }\n";
		
		
		echo "      if (value != '') {\n";
		echo "        var found = false;\n";
		echo "        var element = document.getElementById('{$ctrlname}');\n";
		echo "        for (var i = 0; i < element.length; i++) {\n";
		echo "          if (element.options[i].text == value) {\n";
		echo "            found = true;\n";
		echo "            break;\n";
		echo "          }\n";
		echo "        }\n";
		echo "        if (found != true) {\n";
		echo "          element.options[element.length] = new Option(value, value, false, true);\n";
		echo "          document.getElementById('{$ctrlname}data').value = '';\n";
		echo "        }\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onclick_delete_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      if (element.value != '') {\n";
		echo "        var msg = confirm('".htmlspecialchars(gettext("Do you really want to remove the selected item from the list?"), ENT_QUOTES)."');\n";
		echo "        if (msg == true) {\n";
		echo "          element.options[element.selectedIndex] = null;\n";
		echo "          document.getElementById('{$ctrlname}data').value = '';\n";
		echo "        }\n";
		echo "      } else {\n";
		echo "        alert('".htmlspecialchars(gettext("Select item to remove from the list"), ENT_QUOTES)."');\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onclick_change_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      if (element.value != '') {\n";
		echo "        var value1 = document.getElementById('{$ctrlname}data').value;\n";
		echo "      var valuetype = document.getElementById('{$ctrlname}filetype').value;\n";
		echo "      var valuenice = document.getElementById('{$ctrlname}datanice').value;\n";
		echo "      if (valuetype != '') {\n";
		echo "     var value = valuetype + '|' + valuenice + '|' + value1;\n";
		echo "          }\n";
		//echo "      var value = valuetype +  value1;\n";
		echo "        element.options[element.selectedIndex].text = value;\n";
		echo "        element.options[element.selectedIndex].value = value;\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onsubmit_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      for (var i = 0; i < element.length; i++) {\n";
		echo "        if (element.options[i].value != '')\n";
		echo "          element.options[i].selected = true;\n";
		echo "      }\n";
		echo "    }\n";
		echo "    //]]>\n";
		echo "    </script>\n";
		echo "    <table><tr><td width='40%' color='red'><center><b>In jail</b></center>\n";
		echo "    <select name='{$ctrlname}[]' class='formfld' id='{$ctrlname}' multiple='multiple'  style='width: 350px' onchange='onchange_{$ctrlname}()'>\n";
		if (isset($value) ){
		foreach ($value as $valuek => $valuev) {
			echo "      <option value='{$valuev}' {$optparam}>{$valuev}</option>\n";
		}}
		echo "    </select>\n";
		echo "    <input name='{$ctrlname}deletebtn' type='button' class='formbtn' id='{$ctrlname}deletebtn' value='".htmlspecialchars(gettext("Delete"), ENT_QUOTES)."' onclick='onclick_delete_{$ctrlname}()' /><br />\n";
		echo "    </td>\n";
		echo "    <td width='20%'>\n";
		echo "    <center><input name='{$ctrlname}addbtn' type='button' class='formbtn' id='{$ctrlname}addbtn' value='".htmlspecialchars(gettext(" << "), ENT_QUOTES)."' onclick='onclick_add_{$ctrlname}()' /></center><br />\n";
		echo "    <center><input name='{$ctrlname}changebtn' type='button' class='formbtn' id='{$ctrlname}changebtn' value='".htmlspecialchars(gettext(" Edit "), ENT_QUOTES)."' onclick='onclick_change_{$ctrlname}()' /></center>\n";
		echo "    </td>\n";
		echo "    <td width='40%'>\n";
		echo "		<table><tr><td>Type</td><td>Nice</td><td><center>Command</center></td></tr>\n";
		echo "		<tr><td>\n";
		echo "    		<select name='{$ctrlname}filetype' class='formfld' id='{$ctrlname}filetype' >\n ";
		echo "		  		<option value='prestart'>prestart</option>\n";
		echo "				<option value='afterstart_for_main'>afterstart_for_main</option>\n";
		echo "				<option value='afterstart_for_jail'>afterstart_for_jail</option>\n";
		echo "		  		<option value='prestop_for_main'>prestop_for_main</option>\n";
		echo "		  		<option value='prestop_for_jail'>prestop_for_jail</option>\n";
		echo "		  		<option value='afterstop'>afterstop</option>\n";
		echo "	 		</select></td>\n";
		echo "    <td><select name='{$ctrlname}datanice' class='formfld' id='{$ctrlname}datanice'>\n";
		foreach (range(0, 5) as $nice) {
			$optparam = "";
			if ($nice == $valuenice)
				$optparam .= "selected=\"selected\" ";
			echo "      <option value='{$nice}' {$optparam}>{$nice}</option>\n";
		}
		echo "    </select></td>\n";
		
		//echo "    		<td><input name='{$ctrlname}datanice' type='text' class='formfld' id='{$ctrlname}datanice' size='2' value='' /></td>\n";
		echo "    		<td><input name='{$ctrlname}data' type='text' class='formfld' id='{$ctrlname}data' size='40' value='' /></td></tr></table>\n";
		echo "    </td></tr></table>\n";
	}
}
class HTMLBrigdevfs extends HTMLBaseControl {
	

	function __construct($ctrlname, $title, $value, $description = "") {
		parent::__construct($ctrlname, $title, $value, $description);
	}

	function RenderCtrl() {
		$ctrlname = $this->GetCtrlName();
		$value = $this->GetValue();
		

		echo "    <script type='text/javascript'>\n";
		echo "    //<![CDATA[\n";
		echo "    function onchange_{$ctrlname}() {\n";
		echo "      document.getElementById('{$ctrlname}data').value = document.getElementById('{$ctrlname}').value;\n";
		echo "    }\n";
		echo "    function onclick_add_{$ctrlname}() {\n";
		echo "      var value = document.getElementById('{$ctrlname}data').value;\n";
		echo "      if (value != '') {\n";
		echo "        var found = false;\n";
		echo "        var element = document.getElementById('{$ctrlname}');\n";
		echo "        for (var i = 0; i < element.length; i++) {\n";
		echo "          if (element.options[i].text == value) {\n";
		echo "            found = true;\n";
		echo "            break;\n";
		echo "          }\n";
		echo "        }\n";
		echo "        if (found != true) {\n";
		echo "          element.options[element.length] = new Option(value, value, false, true);\n";
		echo "          document.getElementById('{$ctrlname}data').value = '';\n";
		echo "        }\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onclick_delete_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      if (element.value != '') {\n";
		echo "        var msg = confirm('".htmlspecialchars(gettext("Do you really want to remove the selected item from the list?"), ENT_QUOTES)."');\n";
		echo "        if (msg == true) {\n";
		echo "          element.options[element.selectedIndex] = null;\n";
		echo "          document.getElementById('{$ctrlname}data').value = '';\n";
		echo "        }\n";
		echo "      } else {\n";
		echo "        alert('".htmlspecialchars(gettext("Select item to remove from the list"), ENT_QUOTES)."');\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onclick_change_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      if (element.value != '') {\n";
		echo "        var value = document.getElementById('{$ctrlname}data').value;\n";
		echo "        element.options[element.selectedIndex].text = value;\n";
		echo "        element.options[element.selectedIndex].value = value;\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onsubmit_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      for (var i = 0; i < element.length; i++) {\n";
		echo "        if (element.options[i].value != '')\n";
		echo "          element.options[i].selected = true;\n";
		echo "      }\n";
		echo "    }\n";
		echo "    //]]>\n";
		echo "    </script>\n";
		echo "    <table><tr><td width='40%' color='red'><center><b>In jail</b></center>\n";
		echo "    <select name='{$ctrlname}[]' class='formfld' id='{$ctrlname}' multiple='multiple' size='4' style='width: 350px' onchange='onchange_{$ctrlname}()'>\n";
		if (isset($value)) {
		foreach ($value as $valuek => $valuev) {
			echo "      <option value='{$valuev}' {$optparam}>{$valuev}</option>\n";
		}}
		echo "    </select><br />\n";
		echo "    <input name='{$ctrlname}deletebtn' type='button' class='formbtn' id='{$ctrlname}deletebtn' value='".htmlspecialchars(gettext("Delete"), ENT_QUOTES)."' onclick='onclick_delete_{$ctrlname}()' />\n";
		echo "    </td>\n";
		echo "    <td width='20%'>\n";
		echo "    <center><input name='{$ctrlname}addbtn' type='button' class='formbtn' id='{$ctrlname}addbtn' value='".htmlspecialchars(gettext(" << "), ENT_QUOTES)."' onclick='onclick_add_{$ctrlname}()' /></center><br />\n";
		echo "    <center><input name='{$ctrlname}changebtn' type='button' class='formbtn' id='{$ctrlname}changebtn' value='".htmlspecialchars(gettext(" Edit "), ENT_QUOTES)."' onclick='onclick_change_{$ctrlname}()' /></center><br />\n";
		echo "    </td>\n";
		echo "    <td width='40%'><center>Rule for jail ruleset </center>\n";
		echo "    <input name='{$ctrlname}data' type='text' class='formfld' id='{$ctrlname}data' size='40' value='' />\n";
		echo "    </td></tr></table>\n";
	}
}

class HTMLBrigNetworkBox extends HTMLBaseControl {
	

	function __construct($ctrlname, $title, $value, $description = "") {
		parent::__construct($ctrlname, $title, $value, $description);
	}

	function RenderCtrl() {
		$ctrlname = $this->GetCtrlName();
		$value = $this->GetValue();
		

		echo "    <script type='text/javascript'>\n";
		echo "    //<![CDATA[\n";
		echo "    function onchange_{$ctrlname}() {\n";
		echo "      var value1 = document.getElementById('{$ctrlname}');\n";
		echo "      var result = value1.value.split('|');\n";
		echo "      document.getElementById('{$ctrlname}data').value = result[1];\n";
		echo "      document.getElementById('{$ctrlname}filetype').value = result[0];\n";
		echo "    }\n";
		echo "    function onclick_add_{$ctrlname}() {\n";
		echo "      var value1 = document.getElementById('{$ctrlname}data').value;\n";
		echo "      var valuetype = document.getElementById('{$ctrlname}filetype').value;\n";
		echo "      if (valuetype != '') {\n";
		echo "      var valuetype = valuetype + '|';\n";
		echo "          }\n";
		echo "      var value = valuetype +  value1;\n";
		echo "      if (value != '') {\n";
		echo "        var found = false;\n";
		echo "        var element = document.getElementById('{$ctrlname}');\n";
		echo "        for (var i = 0; i < element.length; i++) {\n";
		echo "          if (element.options[i].text == value) {\n";
		echo "            found = true;\n";
		echo "            break;\n";
		echo "          }\n";
		echo "        }\n";
		echo "        if (found != true) {\n";
		echo "          element.options[element.length] = new Option(value, value, false, true);\n";
		echo "          document.getElementById('{$ctrlname}data').value = '';\n";
		echo "        }\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onclick_delete_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      if (element.value != '') {\n";
		echo "        var msg = confirm('".htmlspecialchars(gettext("Do you really want to remove the selected item from the list?"), ENT_QUOTES)."');\n";
		echo "        if (msg == true) {\n";
		echo "          element.options[element.selectedIndex] = null;\n";
		echo "          document.getElementById('{$ctrlname}data').value = '';\n";
		echo "        }\n";
		echo "      } else {\n";
		echo "        alert('".htmlspecialchars(gettext("Select item to remove from the list"), ENT_QUOTES)."');\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onclick_change_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      if (element.value != '') {\n";
		echo "        var value1 = document.getElementById('{$ctrlname}data').value;\n";
		echo "      var valuetype = document.getElementById('{$ctrlname}filetype').value;\n";
		echo "      if (valuetype != '') {\n";
		echo "      var valuetype = valuetype + '|';\n";
		echo "          }\n";
		echo "      var value = valuetype +  value1;\n";
		echo "        element.options[element.selectedIndex].text = value;\n";
		echo "        element.options[element.selectedIndex].value = value;\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onsubmit_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      for (var i = 0; i < element.length; i++) {\n";
		echo "        if (element.options[i].value != '')\n";
		echo "          element.options[i].selected = true;\n";
		echo "      }\n";
		echo "    }\n";
		echo "    //]]>\n";
		echo "    </script>\n";
		echo "    <table><tr><td width='40%' color='red'><center><b>In jail</b></center>\n";
		echo "    <select name='{$ctrlname}[]' class='formfld' id='{$ctrlname}' multiple='multiple'  style='width: 350px' onchange='onchange_{$ctrlname}()'>\n";
		if (isset($value)) {
		foreach ($value as $valuek => $valuev) {
			echo "      <option value='{$valuev}' {$optparam}>{$valuev}</option>\n";
		}}
		echo "    </select>\n";
		echo "    <input name='{$ctrlname}deletebtn' type='button' class='formbtn' id='{$ctrlname}deletebtn' value='".htmlspecialchars(gettext("Delete"), ENT_QUOTES)."' onclick='onclick_delete_{$ctrlname}()' /><br />\n";
		echo "    </td>\n";
		echo "    <td width='20%'>\n";
		echo "    <center><input name='{$ctrlname}addbtn' type='button' class='formbtn' id='{$ctrlname}addbtn' value='".htmlspecialchars(gettext(" << "), ENT_QUOTES)."' onclick='onclick_add_{$ctrlname}()' /></center><br />\n";
		echo "    <center><input name='{$ctrlname}changebtn' type='button' class='formbtn' id='{$ctrlname}changebtn' value='".htmlspecialchars(gettext(" Edit "), ENT_QUOTES)."' onclick='onclick_change_{$ctrlname}()' /></ center>\n";
		echo "    </td>\n";
		echo "    <td width='40%'>IP4 or IP6 address/netmask, format 192.168.1.1/24<br />\n";
		
		echo "    <select name='{$ctrlname}filetype' class='formfld' id='{$ctrlname}filetype' >\n ";
		 $ifs = array_keys (brig_interface_list());
		 foreach ($ifs as $single_ip ) { $my_ip = $single_ip;
		echo "  		<option value='{$my_ip}'>{$my_ip}</option>\n";
	}	
		echo "		</select>\n";
		echo "    <input name='{$ctrlname}data' type='text' class='formfld' id='{$ctrlname}data' size='40' value='' />\n";
		echo "    </td></tr></table>\n";
	}
}
class HTMLOptionsBox extends HTMLBaseControl {
	var $j_option = "";
	
	function __construct($ctrlname, $title, $value ) {
		parent::__construct($ctrlname, $title, $value );
	}
	function Getj_option() {
		return $this->j_option;
	}

	function Setj_option($j_option) {
		$this->j_option = $j_option;
	}
	function RenderCtrl() {
		$ctrlname = $this->GetCtrlName();
		$value = $this->GetValue();
		$options = $this->Getj_option(); 

		echo "    <script type='text/javascript'>\n";
		echo "    //<![CDATA[\n";
		echo "   function SelectMoveRows(SS1,SS2) {\n";
		echo "      var SelID='';\n";
		echo "      var SelText='';\n";
		echo "      for (i=SS1.options.length - 1; i>=0; i--) {\n";
		echo "       	if (SS1.options[i].selected == true) {\n";
		echo "      		SelID=SS1.options[i].value;\n";
		echo "       		SelText=SS1.options[i].text;\n";
		echo "      		var newRow = new Option(SelText,SelID);\n";
		echo "      		SS2.options[SS2.length]=newRow;\n";
		echo "       		SS1.options[i]=null;\n";
		echo "         		}\n";
		echo "        	}\n";
		echo "      	SelectSort(SS2);\n";
		echo "      }\n";
		echo "      function SelectSort(SelList) {\n";
		echo "      	var ID='';\n";
		echo "   	var Text='';\n";
		echo "    	for (x=0; x < SelList.length - 1; x++) {\n";
		echo "      		for (y=x + 1; y < SelList.length; y++) {\n";
		echo "      			if (SelList[x].text > SelList[y].text) {\n";
	//	echo "      				if (valuetype != '') {\n";
		echo "      ID=SelList[x].value;\n";
		echo "        Text=SelList[x].text;\n";
		echo "      SelList[x].value=SelList[y].value;\n";
		echo "       SelList[x].text=SelList[y].text;\n";
		echo "        SelList[y].value=ID;\n";
		echo "        SelList[y].text=Text;\n";
		echo "        } } } }\n";
		echo "    function onsubmit_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      for (var i = 0; i < element.length; i++) {\n";
		echo "        if (element.options[i].value != '')\n";
		echo "          element.options[i].selected = true;\n";
		echo "      }\n";
		echo "    }\n";
		echo "    //]]>\n";
		echo "    </script>\n";
		echo "    <table><tr><td width='40%' color='red'><center><b>In jail</b></center>\n";
		echo "    <select name='{$ctrlname}[]' class='formfld' id='{$ctrlname}' multiple='multiple' size='8' style='width: 350px' >\n";
		if (isset($value)) { foreach ($value as $valuek => $valuev) {
			echo "      <option value='{$valuev}' >{$valuev}</option>\n";
				      }
			}
		echo "    </select>\n";
		echo "    </td>\n";
		echo "    <td width='20%'>\n";
		echo "    <center><input name='{$ctrlname}addbtn' type='button' class='formbtn' id='{$ctrlname}addbtn' value='".htmlspecialchars(gettext(" << "), ENT_QUOTES)."' onClick='SelectMoveRows(document.iform.{$ctrlname}optiontype,document.iform.{$ctrlname})' /></center><br />\n";
		
		echo "    <center><input name='{$ctrlname}deletebtn' type='button' class='formbtn' id='{$ctrlname}deletebtn' value='".htmlspecialchars(gettext(" >> "), ENT_QUOTES)."' onClick='SelectMoveRows(document.iform.{$ctrlname},document.iform.{$ctrlname}optiontype)' /></center>\n";
		echo "    </td>\n";
		echo "    <td width='40%'><center>Options </center>\n";
		echo "    <select name='{$ctrlname}optiontype' class='formfld' id='{$ctrlname}optiontype' multiple='multiple' size='8' style='width: 350px'> ";

	if (is_array($value)) {
		$options1 = array_diff($options, $value);
		foreach ($options1 as $optionsv) {
		echo "      <option value='{$optionsv}' {$optparam}>{$optionsv}</option>\n";
			}
		echo "    </td></tr></table>\n";
		} else {
		foreach ($options as $optionsv) {
		echo "      <option value='{$optionsv}' {$optparam}>{$optionsv}</option>\n";
			}
		echo "    </td></tr></table>\n";
		}
	}
}
class HTMLzfs_set extends HTMLBaseControl {
	
	function __construct($ctrlname, $title, $value, $description = "") {
		parent::__construct($ctrlname, $title, $value, $description);
	}
	function Getj_dataset() {
		return $this->j_dataset;
	}

	function Setj_dataset($j_dataset) {
		$this->j_dataset = $j_dataset;
	}
	function RenderCtrl() {
		$ctrlname = $this->GetCtrlName();
		$value = $this->GetValue();
		$options = $this->Getj_dataset();

		echo "    <script type='text/javascript'>\n";
		echo "    //<![CDATA[\n";
		echo "    function onchange_{$ctrlname}() {\n";
		echo "      var value1 = document.getElementById('{$ctrlname}');\n";
		echo "      var result = value1.value.split('|');\n";
		echo "      document.getElementById('{$ctrlname}data').value = result[1];\n";
		echo "      document.getElementById('{$ctrlname}filetype').value = result[0];\n";
		echo "    }\n";
		echo "    function onclick_add_{$ctrlname}() {\n";
		echo "      var value1 = document.getElementById('{$ctrlname}data').value;\n";
		echo "      var valuetype = document.getElementById('{$ctrlname}filetype').value;\n";
		echo "      if (valuetype != '') {\n";
		echo "      var valuetype = valuetype + '|';\n";
		echo "          }\n";
		echo "      var value = valuetype +  value1;\n";
		echo "      if (value != '') {\n";
		echo "        var found = false;\n";
		echo "        var element = document.getElementById('{$ctrlname}');\n";
		echo "        for (var i = 0; i < element.length; i++) {\n";
		echo "          if (element.options[i].text == value) {\n";
		echo "            found = true;\n";
		echo "            break;\n";
		echo "          }\n";
		echo "        }\n";
		echo "        if (found != true) {\n";
		echo "          element.options[element.length] = new Option(value, value, false, true);\n";
		echo "          document.getElementById('{$ctrlname}data').value = '';\n";
		echo "        }\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onclick_delete_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      if (element.value != '') {\n";
		echo "        var msg = confirm('".htmlspecialchars(gettext("Do you really want to remove the selected item from the list?"), ENT_QUOTES)."');\n";
		echo "        if (msg == true) {\n";
		echo "          element.options[element.selectedIndex] = null;\n";
		echo "          document.getElementById('{$ctrlname}data').value = '';\n";
		echo "        }\n";
		echo "      } else {\n";
		echo "        alert('".htmlspecialchars(gettext("Select item to remove from the list"), ENT_QUOTES)."');\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onclick_change_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      if (element.value != '') {\n";
		echo "        var value1 = document.getElementById('{$ctrlname}data').value;\n";
		echo "      var valuetype = document.getElementById('{$ctrlname}filetype').value;\n";
		echo "      if (valuetype != '') {\n";
		echo "      var valuetype = valuetype + '|';\n";
		echo "          }\n";
		echo "      var value = valuetype +  value1;\n";
		echo "        element.options[element.selectedIndex].text = value;\n";
		echo "        element.options[element.selectedIndex].value = value;\n";
		echo "      }\n";
		echo "    }\n";
		echo "    function onsubmit_{$ctrlname}() {\n";
		echo "      var element = document.getElementById('{$ctrlname}');\n";
		echo "      for (var i = 0; i < element.length; i++) {\n";
		echo "        if (element.options[i].value != '')\n";
		echo "          element.options[i].selected = true;\n";
		echo "      }\n";
		echo "    }\n";
		echo "    //]]>\n";
		echo "    </script>\n";
		echo "    <table><tr><td width='40%' color='red'><center><b>In jail</b></center>\n";
		echo "    <select name='{$ctrlname}[]' class='formfld' id='{$ctrlname}' multiple='multiple'  style='width: 350px' onchange='onchange_{$ctrlname}()'>\n";
		if (isset($value)) {
		foreach ($value as $valuek => $valuev) {
			echo "      <option value='{$valuev}' {$optparam}>{$valuev}</option>\n";
		}}
		echo "    </select>\n";
		echo "    <input name='{$ctrlname}deletebtn' type='button' class='formbtn' id='{$ctrlname}deletebtn' value='".htmlspecialchars(gettext("Delete"), ENT_QUOTES)."' onclick='onclick_delete_{$ctrlname}()' /><br />\n";
		echo "    </td>\n";
		echo "    <td width='20%'>\n";
		echo "    <center><input name='{$ctrlname}addbtn' type='button' class='formbtn' id='{$ctrlname}addbtn' value='".htmlspecialchars(gettext(" << "), ENT_QUOTES)."' onclick='onclick_add_{$ctrlname}()' /></center><br />\n";
		echo "    <center><input name='{$ctrlname}changebtn' type='button' class='formbtn' id='{$ctrlname}changebtn' value='".htmlspecialchars(gettext(" Edit "), ENT_QUOTES)."' onclick='onclick_change_{$ctrlname}()' /></ center>\n";
		echo "    </td>\n";
		
		echo "    <td width='40%'><table><tr><td width='50%'><center>datasets</center></td><td>Jail's mount point</td></tr>\n";
		
		echo "    <tr><td width='40%'><select name='{$ctrlname}filetype' class='formfld' id='{$ctrlname}filetype' >\n ";
		if (isset($value)){
		foreach ($options as $optionsv) {
		echo "      <option value='{$optionsv[1]}' {$optparam}>{$optionsv[1]}</option>\n";
			}}
		
		
		echo "    </select></td><td><input name='{$ctrlname}data' type='text' class='formfld' id='{$ctrlname}data' size='30' value='' /></td></tr></table>\n";
		echo "    </td></tr></table>\n";
	}
}
function html_optionsbox($ctrlname, $title, $value, $j_opt, $required = false, $readonly = false) {
	$ctrl = new HTMLOptionsBox($ctrlname, $title, $value);
	//$ctrl->GetOptions($options);
	$ctrl->Setj_option($j_opt);
	$ctrl->SetRequired($required);
	$ctrl->SetReadOnly($readonly);
	$ctrl->Render();
}
function html_brigdevfs_box($ctrlname, $title, $value, $desc,  $required = false, $readonly = false) {
 	foreach ($value as &$curString)
          { $curString = htmlspecialchars($curString, ENT_QUOTES); }
	$ctrl = new HTMLBrigdevfs($ctrlname, $title, $value, $desc);
	$ctrl->SetRequired($required);
	$ctrl->SetReadOnly($readonly);	
	$ctrl->Render();
}

function html_brig_network_box($ctrlname, $title, $value, $desc,  $required = false, $readonly = false) {
	$ctrl = new HTMLBrigNetworkBox($ctrlname, $title, $value, $desc);
	$ctrl->SetRequired($required);
	$ctrl->SetReadOnly($readonly);
	
	$ctrl->Render();
}
function html_brig_command_box($ctrlname, $title, $value, $desc,  $required = false, $readonly = false) {
	$ctrl = new HTMLBrigCommandBox($ctrlname, $title, $value, $desc);
	$ctrl->SetRequired($required);
	$ctrl->SetReadOnly($readonly);
	$ctrl->Render();
}
function html_zfs_box($ctrlname, $title, $value, $j_dataset, $desc,  $required = false, $readonly = false) {
	$ctrl = new HTMLzfs_set($ctrlname, $title, $value, $desc);
	$ctrl->Setj_dataset($j_dataset);
	$ctrl->SetRequired($required);
	$ctrl->SetReadOnly($readonly);	
	$ctrl->Render();
}
function brig_interface_list() {
	/* build interface list with netstat */
	exec("/usr/bin/netstat -inW -f link", $linkinfo);
	array_shift($linkinfo);

	$iflist = array();

	foreach ($linkinfo as $link) {
		$alink = preg_split("/\s+/", $link);
		$ifname = chop($alink[0]);

		if (substr($ifname, -1) === "*")
			$ifname = substr($ifname, 0, strlen($ifname) - 1);
		/* add the plip interface to be excluded too */
		if (!preg_match("/^(ppp|sl|gif|faith|lo|plip|ipfw|usbus|carp)/", $ifname)) {
			$iflist[$ifname] = array();

			$iflist[$ifname]['mac'] = chop($alink[3]);
			$iflist[$ifname]['up'] = false;
		}
	}

	return $iflist;
}
