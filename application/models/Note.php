<?php

class Note
{
    public $subject;
    public $noteText;

    function Note($subject, $noteText)
    {
        $this->subject  = $subject;
        $this->noteText = $this->cleanNote($noteText);
        App_Xml_Generic::parseXMLcoding($this->noteText);
    }
    
    function generateNoteXML($notesNode, $namespaceArch)
    {
        $noteNode = $notesNode->addChild('note', '', $namespaceArch);
        $noteNode->addChild('string', $this->noteText, $namespaceArch);
    }
    
    /*public function setProjectNotes($shortDes, $longDes)
    {
        $this->shortDes = $shortDes;
        $this->longDes  = $longDes;
    }
    
    public function setPropertyNotes($varDes, $propDes)
    {
        $this->varDes   = $varDes;
        $this->propDes  = $propDes;
    }
    
    public function setDiary($diaryDo)
    {
        $this->diaryDo = $diaryDo;
    }
    */


    
    
    /*function makeNoteXML($parentNode)
    {
        $this->getItemNotes();
        $obsNotes = $this->obsNotes;
        
        //if there are no notes, return:
        if(empty($obsNotes) && empty($this->longDes) && empty($this->varDes) && empty($this->propDes))
            return;
         
        //create notes XML node:       
        $notesNode = $parentNode->addChild('notes', '', $this->namespaceArch);
        if(!empty($obsNotes))
        {
            foreach($obsNotes AS $note)
            {
                $noteXML = $notesNode->addChild('note', '', $this->namespaceArch);
                $noteXML->addChild('string', $note, $this->namespaceArch);	
            }
        }
        if(!empty($this->shortDes)){
            $noteXML = $notesXML->addChild('note', '', $this->namespaceArch);
            $noteXML->addAttribute('type', 'short_des');
            $noteXML->addChild('string', $this->shortDes, $this->namespaceArch);
        }
        if(!empty($this->longDes)){
            $noteXML = $notesXML->addChild('note', '', $this->namespaceArch);
            $noteXML->addAttribute('type', 'long_des');
            $noteXML->addChild('string', $this->longDes, $this->namespaceArch);
        }
        if(!empty($this->varDes))
        {
            $noteXML = $notesXML->addChild('note', '', $this->namespaceArch);
            $noteXML->addAttribute('type', 'var_des');
            $noteXML->addChild('string', $this->varDes, $this->namespaceArch);
        }
        if(!empty($this->propDes))
        {
            $noteXML = $notesXML->addChild('note', '', $this->namespaceArch);
            $noteXML->addAttribute('type', 'prop_des');
            $noteXML->addChild('string', $this->propDes, $this->namespaceArch);
        }       
    }*/
    
    function cleanNote($noteText)
    {
        $note = strtr($noteText, "", "\n'\n'");
        $note = str_replace("&nbsp;"," ", $noteText);
        $note = str_replace("&deg;","&#176;", $noteText);
        $note = str_replace("&rsquo;","&#8217;", $noteText);
        $note = str_replace("&lsquo;","&#8216;", $noteText);
        $note = str_replace("&rdquo;","&#8221;", $noteText);
        $note = str_replace("&ldquo;","&#8220;", $noteText);
        $note = str_replace("&ndash;","&#8211;", $noteText);
        $note = str_replace("<br>","<br/>", $noteText);
        $note = str_replace("<b>","<strong>", $noteText);
        $note = str_replace("</b>","</strong>", $noteText);
        $note = str_replace("<i>","<em>", $noteText);
        $note = str_replace("</i>","</em>", $noteText);
        $note = str_replace("",chr(13),$noteText);
        $note = str_replace("http://www.opencontext.org/database/space.php?item=","http://opencontext/subjects/", $noteText);
        return $noteText;
    }
}

?>