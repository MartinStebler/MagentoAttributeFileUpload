<?php
/**
 * Created by PhpStorm.
 * User: mstebler
 * Date: 15.06.17
 * Time: 10:29
 */

namespace Dev98\Datasheet\Model\Product\Attribute\Backend;

use Magento\Framework\App\Filesystem\DirectoryList;

class Datasheet extends \Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend
{
    const MEDIA_SUBFOLDER = 'datasheet';

    protected $_uploaderFactory;
    protected $_filesystem;
    protected $_fileUploaderFactory;
    protected $_logger;

    /**
     * Construct
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\File\UploaderFactory $uploaderFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\File\UploaderFactory $uploaderFactory
    ) {
        $this->_filesystem = $filesystem;
        $this->_uploaderFactory = $uploaderFactory;
        $this->_logger = $logger;
    }

    public function afterSave($object)
    {
        $attributeName = $this->getAttribute()->getName();
        $fileName = $this->uploadFileAndGetName($attributeName, $this->_filesystem->getDirectoryWrite(DirectoryList::MEDIA)->getAbsolutePath(self::MEDIA_SUBFOLDER));

        if ($fileName) {
            $object->setData($attributeName, $fileName);
            $this->getAttribute()->getEntity()->saveAttribute($object, $attributeName);
        }

        return $this;
    }

    public function uploadFileAndGetName($input, $destinationFolder)
    {
        try {
            $uploader = $this->_uploaderFactory->create(array('fileId' => 'product['.$input.']'));
            $uploader->setAllowedExtensions(['pdf']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(true);
            $uploader->setAllowCreateFolders(true);
            $uploader->save($destinationFolder);

            return $uploader->getUploadedFileName();
        } catch (\Exception $e) {
            if ($e->getCode() != \Magento\Framework\File\Uploader::TMP_NAME_EMPTY) {
                throw new \FrameworkException($e->getMessage());
            }
        }

        return '';
    }
}