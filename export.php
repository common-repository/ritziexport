<?php 
class XfrExport
{
    public $xml_file_path;

    /**
     * @param array
     */
    public function __construct($options)
    {
        foreach ($options as $key => $option) {
            $this->{$key} = $option;
        }

        if ($upload_dir = (object)wp_get_upload_dir()) {
            $this->xml_file_path = $upload_dir->basedir . "/" . XmlForRitzi::XML_FILE;
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }

    /**
     * Export products
     * 
     * @return boolean
     */
    public function export()
    {
        if ($this->xml_file_path) {
            $xml = new DOMDocument();
            $xml->encoding = 'utf-8';
            $xml->xmlVersion = '1.0';
            $xml->formatOutput = true;

            $rss = $xml->createElement("rss");
            $channel = $xml->createElement('channel');

            $title = $xml->createElement('title', $this->getOption('blogname'));
            $link = $xml->createElement('link', $this->getOption('siteurl'));
            $description = $xml->createElement('description', $this->getOption('blogdescription'));

            $channel->appendChild($title);
            $channel->appendChild($link);
            $channel->appendChild($description);

            if ($products = $this->getProducts()) {
                foreach ($products as $product) {
                    $item = $xml->createElement('item');
                    $item = $this->getProductElement($product, $xml, $item);
                    $channel->appendChild($item);
                }
            }

            $rss->appendChild($channel);
            $rss_node = $xml->appendChild($rss);
            $rss_node->setAttribute("version","2.0");
            $xml->save($this->xml_file_path);

            return true;
        }
        return false;
    }

    /**
     * Get product element
     *
     * @param WC_Product_Simple|WC_Product_Variable $product
     * @param DOMDocument $xml
     * @param DOMElement $item
     * @return DOMElement
     */
    public function getProductElement($product, DOMDocument $xml, DOMElement $item)
    {
        $data = $product->get_data();

        $id = $xml->createElement('id', $data['id']);
        $item->appendChild($id);

        $title = $xml->createElement('title', $data['name']);
        $item->appendChild($title);

        $description = $xml->createElement('description', $data['description']);
        $item->appendChild($description);

        $price = $xml->createElement('price', ($p = $data['price']) ? $p : 0);
        $item->appendChild($price);

        $status = $xml->createElement('status', ($data['status'] == 'publish') ? 1 : 0);
        $item->appendChild($status);

        if ($data['status'] == 'publish') {
            $link = $xml->createElement('link', get_permalink($data['id']));
            $item->appendChild($link);
        }

        if ($image_id  = $product->get_image_id()) {
            $image_link = $xml->createElement('image_link', wp_get_attachment_image_url( $image_id, 'full' ));
            $item->appendChild($image_link);
        }

        $quantity = $xml->createElement('quantity', ($stock_quantity = $data['stock_quantity']) ? $stock_quantity : 0);
        $item->appendChild($quantity);

        if (isset($data['attributes']) && $data['attributes']) {
            foreach ($data['attributes'] as $attribute) {
                if ($dataAttribute = $attribute->get_data()) {
                    $option = $xml->createElement('option');
                    $name = $xml->createElement('name', $dataAttribute['name']);
                    $option->appendChild($name);
                    foreach ($dataAttribute['options'] as $optionValue) {
                        $value = $xml->createElement('value', $optionValue);
                        $option->appendChild($value);
                    }
                    $item->appendChild($option);
                }
            }
        }

        return $item;
    }

    /**
     * Get products
     * 
     * @return array
     */
    private function getProducts()
    {
        return wc_get_products(['orderby' => 'id', 'limit' => $this->quantity_products]);
    }

    /**
     * Get option
     *
     * @param string
     * @return mixed
     */
    private function getOption($name)
    {
        return get_option($name);
    }
}