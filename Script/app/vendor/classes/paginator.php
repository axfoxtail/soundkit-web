<?php
class Paginator {
    public $total;
    private $db;
    private $limit;
    private $page;
    private $query;
    private $baseUrl = "";
    private $links = 7;
    private $listClass = "pagination";
    private $appends = array();
    private $param = array();

    public function __construct($query,$param = array(), $limit = 10, $links = 7, $page = null, $baseUrl = '') {
        $this->db = Database::getInstance();
        $this->query = $query;

        $result = $this->db->query($this->query, $param);
        if($result) $this->total = $result->rowCount();

        $this->page = Request::instance()->input('page', 1);
        $this->validatePageNumber();
        $this->limit = $limit;
        $this->links = $links;
        $this->baseUrl = ($baseUrl) ? $baseUrl : getFullUrl();
        $this->param = $param;
    }

    function validatePageNumber() {
        $this->page = (int) str_replace("-", '', $this->page);
    }

    public function setListClass($class = "") {
        $this->listClass = $class;
        return $this;
    }

    public function append($param = array()) {
        $this->appends = $param;
        return $this;
    }

    public function results() {
        if($this->limit == "all") {
            $query = $this->query;
        } else {
            $query = $this->query." LIMIT ".(($this->page - 1) * $this->limit).", {$this->limit}";
        }
        $query = $this->db->query($query, $this->param);
        if($query) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return array();
    }

    public function links($ajax = false) {
        if($this->limit == 'all') return '';
        $last = ceil($this->total / $this->limit);
        $start = (($this->page - $this->links) > 0) ? $this->page - $this->links : 1;
        $end = (($this->page + $this->links) < $last) ? $this->page + $this->links : $last;
        $ajax = ($ajax) ? 'ajax="true"' : null;

        $html = "<ul class='".$this->listClass."' style='margin-top:50px'>";
        $class = ($this->page == 1) ? "disabled" : "";
        $html .= "<li class='".$class."'> <a data-ajax='true' ".$ajax."  href='".$this->getLink((($this->page - 1) == 0) ? 1 : $this->page - 1)."' class=' page-link'><i class='la la-angle-left'></i></a>";


        if($start > 1) {
            $html .= "<li class='page-item'><a data-ajax='true' class='page-link' ".$ajax." href='".$this->getLink(1)."'>1</a></li>";
            $html .= "<li class='disabled page-item'><span class='page-link'>...</span></li>";
        }

        for($i = $start; $i <= $end; $i++) {
            $class = ($this->page == $i) ? "active" : "";
            $style = ($this->page == $i) ? "style='color: white !important; border-radius: 2px !important; font-weight: bold !important;'" : null;
            $html .= "<li class='".$class." page-item' ><a data-ajax='true' class='page-link' {$style} ".$ajax." href='".$this->getLink($i)."'>".$i."</a></li>";
        }

        if($end < $last) {
            $html .= "<li class='disabled page-item'><span class='page-link'>...</span></li>";
            $html .= "<li class='page-item'><a data-ajax='true' class='page-link' ".$ajax." href='".$this->getLink($last)."'>{$last}</a>";
        }

        $class = ($this->page == $last) ? "disabled" : "";
        $html .= "<li class='".$class." page-item'> <a data-ajax='true' ".$ajax." href='".$this->getLink(($last == $this->page) ? $last : $this->page + 1)."' class='page-link'><i class='la la-angle-right'></i></a>";
        $html .= "</ul>";

        return $html;
    }

    public function getLink($page) {
        $link = $this->baseUrl;
        $appends['page'] = $page;

        $appends = array_replace($this->appends, $appends);
        $appends = array_replace($_GET, $appends);
        $appends = array_replace($_POST, $appends);

        $i = true;
        //$already = preg_match('#?#', $this->baseUrl);
        foreach($appends as $key => $value) {
            if($i) {
                $link .= '?';
                $i = !$i;
            } else {
                $link .= '&';
            }
            $link .= $key.'='.$value;
        }
        return $link;
    }
}