<?php

/**
 * Created by PhpStorm.
 * User: Alfie
 * Date: 2016/04/03
 * Time: 11:17 PM
 */
class template
{

    /**
     * Include out page class, and build a page object to manage the content and structure of the page
     * @param Object our registry object
     */
    public function __construct(Registry $registry){

        $this->registry = $registry;
        include(FRAMEWORK_PATH .'/registry/page.class.php'); //Not sure about the file extensions..
        $this->page = new Page($this->registry);
    }

    /**
     * Set the content of the page based on a number of templates pass template file locations as
     * individual arguments
     * @return void
     */
    public function buildFromTemplates(){

        $bits = func_get_args();
        $content="";
        foreach ($bits as $bit){
            if(strpos($bit, 'views/') === false){
                $bit = 'views/' .$this->registry->getSetting('view').'/template/' .$bit;
            }
            if(file_exists($bit) == true){
                $content .=file_get_contents($bit);
            }
        }
        $this->page->setContent($content);
    }

    /**
     * Add a template bit form a view to our page
     * @param String $tag the tag where we insert the template e.g
     * {hello}
     * @param String $bit the template bit (path to file, or just the filename)
     * @return void
     */
    public function addTemplateBit($tag,$bit){

        if(strpos($bit,'views/')===  false){
            $bit = 'views/'.$this->registry->getSetting('views') .'/template/'.$bit;

        }
        $this->page->addTempalteBit($tag, $bit);
    }

    /**
     * Take the template bits from the view and insert them into our page content
     * updates the pages content
     * @return void
     */
    private function replaceBits(){

        $bits = $this->page->getBits();
        //Loop through template bits
        foreach ($bits as $tag => $template){
            $templateContent = file_get_contents($template);
            $newContent = str_replace('{' . $tag . '}' ,$templateContent, $this->page->getContent());
            $this->page->setContent($newContent);
        }
    }
    
    /**
     * Replace tags in our page with content 
     * @return void 
     */
    private function repalaceTags($pp =false){
        
        //get the tags in the page 
        if($pp == false){
            $tags = $this->page->getTags();
        }
        else{
            $tags = $this->page->getPPTags();
        }
        
        //go through them all
        foreach ($tags as $tag=>$data){

            //if the tag is an array, then we need to do more than a simple find and replace!
            if (is_array($data)){

                if($data[0] == 'SQL'){
                    //it is cached query... replace tags from the database
                    $this->repalaceDBtags($tag,$data[1]);
                }
                elseif ($data[0] == 'DATA'){
                    //it is cached data... replace tags from cached data
                    $this->replaceDataTags($tag , $data[1]);
                }
            }
            else{
                //replace the content
                $newContent = str_replace('{'.$tag.'}',$data , $this->page->getContent());
                //update the pages content
                $this->page->setContent($newContent);
            }
        }
    }

    /**
     * Replace content on the page with data from the database
     * @param String $tag the tag defining the area of content
     * @param int $cacheId the queries ID in the query cache
     * @return void
     */
    private function replaceDBTags($tag,$cacheId){

        $block ='';
        $blockOld = $this->page->getBlock($tag);
        $apd = $this->page->getAdditionalParsingData();
        $apdkeys = array_keys($apd);
        //foreach record related to the query....
        while ($tags = $this->registry->getObject('db')->resultsFromCache($cacheId)){

            $blockNew = $blockOld;

            //Do we have APD tags
            if (in_array($tag, $apdkeys)){

                //Yes we do !
                foreach ($tags as $ntag =>$data){

                    $blockNew = str_replace("{" . $ntag ."}",$data,$blockNew);

                    //Is this tag the on with extra parsing to be done ?
                    if(array_key_exists( $ntag ,$apd[$tag])){

                        //Yes it is
                        $extra = $apd[$tag][$ntag];

                        //does the tag equal the condition ?
                        if ($data == $extra['condition']){

                            //Yep replace the extratag with the data
                            $blockNew =str_replace("{" . $extra['tag'] . "}" ,$extra['data'], $blockNew);

                        }
                        else{

                            //remove the extra tag - it ain't used!
                            $blockNew=str_replace("{" .$extra['tag'] . "}",'' ,$blockNew);
                        }
                    }
                }
            }
            else{

                //Create a new block of content with the results replaced into it
                foreach ($tags  as $ntag => $data){
                    $blockNew = str_replace("{" . $ntag . "}",$data,$blockNew);
                }
            }
            $block .=$blockNew;
        }

        $pageContent = $this-> page-> getContent();
        //remove the separator in the template, cleaner HTML
        $newContent = str_replace('<!-- START ' .$tag . '-->' . $blockOld .'<!-- END , . $tag . ,-->', $block, $pageContent);

        //update the page content
        $this->page-> setContent($newContent);
    }

    /**
     * Replace content on the page with data from the cache
     * @param String $tag the tag defining the area of content
     * @param int $cacheId thee datas ID in the data cache
     * @return void
     */
    private function replaceDataTags( $tag , $cacheId ){

        $blockOld = $this->page->getBlock($tag);
        $block = '';
        $tags = $this->registry->getObject('db')->dataFromCache( $cacheId);


        foreach ( $tag as $key => $tagsdata){

            $blockNew = $blockOld;

            foreach ($tags as $taga => $data){

                $blockNew = str_replace("{" .$taga ."}" ,$data ,$blockNew);
            }

            $block = $blockNew;
        }

        $pageContent = $this->page->getContent();
        $newContent = str_replace('<!-- START '.$tag.'-->'.$blockOld. '<!-- END '.$tag. '-->', $block, $pageContent);
        $this->page->setContent($newContent);
    }

    /**
     * Convert an array of data into some tags
     * @param array the data
     * @param string a prefix which is added to field name to create the tag name
     * @return void
     */
    public function dataToTags( $data , $prefix ){

        foreach( $data as $key => $content ){

            $this->page->addTag($prefix.$key, $content);
        }
    }

    /**
     * Take the title we set in the page object, and insert them into the view
     */
    public function parseTitle(){

        $newContent = str_replace('<title>','<title>'. $this->page->getTitle(), $this->page->getContent());
        $this->page->setContent($newContent);
    }


    /**
     * Parse the page object into some output
     * @return void
     */
    public function parseOutput(){

        $this->replaceBits();
        $this->repalaceTags(false);
        $this->replaceBits();
        $this->repalaceTags(false);
        $this->parseTitle();
    }

}