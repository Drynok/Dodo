<?php
namespace domo;

require_once('ChildNode.php');

class DocumentType extends ChildNodeLeaf
{
        protected const _nodeType = DOCUMENT_TYPE_NODE;

        public function __construct(Document $doc, string $name, string $publicId='', string $systemId='')
        {
                parent::__construct();

                $this->_ownerDocument = $doc;
                $this->_name = $name;
                $this->_nodeName = $name; // spec; let Node::nodeName handle
                $this->_nodeValue = NULL; // spec; let Node::nodeValue handle
                $this->_publicId = $publicId;
                $this->_systemId = $systemId;
        }

        public function name(void)
        {
                return $this->_name;
        }
        public function publicId(void)
        {
                return $this->_publicId;
        }
        public function systemId(void)
        {
                return $this->_systemId;
        }

        /* Methods delegated in Node */
        public function _subclass_cloneShallow
        {
                return new DocumentType($this->_ownerDocument, $this->_name, $this->_publicId, $this->_systemId);
        }

        public function _subclass_isEqual(Node $node)
        {
                return $this->_name === $node->_name && $this->_publicId === $node->_publicId && $this->_systemId === $node->_systemId;
        }
}
