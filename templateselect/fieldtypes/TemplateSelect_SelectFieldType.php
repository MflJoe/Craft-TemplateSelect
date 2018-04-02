<?php
namespace Craft;

class TemplateSelect_SelectFieldType extends BaseFieldType
{
    private $directoryPaths = [];
    private $filteredTemplates = [];
    private $templatesPath;
    
    public function __construct()
    {
        $this->filteredTemplates[''] = Craft::t('No template selected');
        $this->templatesPath = $siteTemplatesPath = craft()->path->getSiteTemplatesPath();
    }

    public function getName()
    {
        return Craft::t('Template Select');
    }

    public function defineContentAttribute()
    {
        return AttributeType::String;
    }

    public function getInputHtml($name, $value)
    {
        $this->getDirectoryPaths();
        $this->getTemplates();

        // Render field
        return craft()->templates->render('_includes/forms/select', array(
            'name'    => $name,
            'value'   => $value,
            'options' => $this->filteredTemplates,
        ));
    }

    private function getDirectoryPaths()
    {

        // Check if the templates path is overriden by configuration
        // TODO: Normalize path
        $limitToSubfolder = craft()->config->get('templateselectSubfolder');

        if ($limitToSubfolder) {
            if (is_array($limitToSubfolder)) {
                foreach ($limitToSubfolder as $subFolder) {
                    $subfolder = rtrim($subFolder, '/');
                    
                    $directoryPath = $this->templatesPath . $subFolder . '/';
                    
                    $this->validateTemplatePath($directoryPath);
                    
                    $this->directoryPaths[] = $directoryPath;
                }
            } else {
                $subfolder = rtrim($limitToSubfolder, '/');
                
                $directoryPath = $this->templatesPath . $subfolder . '/';
                
                $this->validateTemplatePath($directoryPath);
                
                $this->directoryPaths[] = $directoryPath;
            }
        } else {
            $this->directoryPaths[] = $this->templatesPath;
        }
        
        return;
    }
    
    private function getTemplates()
    {
        foreach ($this->directoryPaths as $directoryPath) {

            // Get folder contents
            $templates = IOHelper::getFolderContents($directoryPath, true);

            // Turn array into ArrayObject
            $templates = new \ArrayObject($templates);

            // Iterate over template list
            for ($list = $templates->getIterator(); $list->valid(); $list->next()) {
                $filename = $list->current();
                
                // Get parts of full template path
                $parts = pathinfo($filename);

                // set templates filename (without path)
                $filename = $parts['basename'];
                
                // remove absolute path from template path to make craft template compatible
                $localisedPath = $parts['dirname'];
                $localisedPath = str_replace($this->templatesPath, '', $localisedPath);
                
                // make localised path windows server compatible
                $normalisedPath = str_replace("\\", "/", $localisedPath) . '/';
                
                // append filename to localised directory if applicable
                $fullPath = ($normalisedPath) ? $normalisedPath . $filename : $filename;
                
                $isTemplate = preg_match("/(.html|.twig)$/u", $filename);
                
                if ($isTemplate) {
                    $this->filteredTemplates[$fullPath] = $filename;
                }
            }
        }
    }
    
    private function validateTemplatePath($templatesPath)
    {
        if (! IOHelper::folderExists($templatesPath)) {
            throw new \InvalidArgumentException('(Template Select) Folder doesn\'t exist: ' . $templatesPath);
        }
    }
}
