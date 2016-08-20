<?php

/**
 * Created by PhpStorm.
 * User: William
 * Date: 8/6/2016
 * Time: 1:46 PM
 */
include_once(__DIR__.'/../../config/global.php');
include_once(__DIR__.'/../../library/db/Connect.class.php');
include_once(__DIR__.'/../../library/db/TableAdapter.class.php');
include_once(__DIR__.'/../../model/class/Categories.php');
include_once(__DIR__.'/../../model/class/Authors.php');
include_once(__DIR__.'/../../model/class/User.php');
include_once(__DIR__.'/../../model/class/Notifications.php');
class Books
{

    private $db;
    private $bookId;
    private $books;
    private $inJSON;
    private $page;
    private $filters;
    private $orders;
    private $foundRows;
    private $returnStats;
    private $properties;

    /**
     * Books constructor.
     */
    public function __construct($bookId = null)
    {
        $this->bookId = $bookId;
        $this->db = new Connect(Connect::DBSERVER);
        $this->page = 1;
        $this->returnStats = false;
    }

    public function setInJson($inJSON=true){
        $this->inJSON = $inJSON;
    }

    public function setReturnStats($returnStats=true){
        $this->returnStats = $returnStats;
    }

    public function getBook()
    {
        if(isset($this->bookId)){
            $this->loadBooks();
            return $this->getResult();
        }else{
            return null;
        }
    }
    public function getBookById($bookId)
    {
        $this->bookId = $bookId;
        $this->loadBooks();
        if(isset($this->books[0])){
            $authors = new Authors();
            $this->books[0]["authors"] = $authors->getAuthorsByBookId($bookId);
            if(!empty($this->books[0]["user_id"])){
                $user = new User($this->books[0]["user_id"]);
                $this->books[0]["user"] = $user->getUser();
            }
        }
        return $this->getResult();
    }
    public function getBooksByPage($page,$limit=BOOKS_VIEW_LIMIT_DEFAULT)
    {
        $this->page = $page;
        $this->loadBooks($this->page,$limit);
        return $this->getResult();
    }
    public function getBooksByCategory($categoryId,$page=1,$limit=BOOKS_VIEW_LIMIT_DEFAULT)
    {
        $this->page = $page;
        $this->filters = ["`book`.`category_id` = " . $categoryId];
        $this->loadBooks($this->page,$limit);
        return $this->getResult();
    }
    public function getBooksByOwner($userId,$page=1,$limit=BOOKS_VIEW_LIMIT_DEFAULT)
    {
        $this->page = $page;
        $this->filters = ["`book`.`user_id` = " . $userId];
        $this->loadBooks($this->page,$limit);
        return $this->getResult();
    }
    public function getBooksRecomended(){

        $this->filters = ["`book`.`recommended` = 1"];
        $this->loadBooks(1,BOOKS_VIEW_LIMIT_RECOMENDED);
        return $this->getResult();
    }
    public function getBooksLatest(){

        $this->orders = ["`book`.`enter_date` DESC"];
        $this->loadBooks(1,BOOKS_VIEW_LIMIT_LATEST);
        return $this->getResult();
    }

    public function getBooksPersonalRecommendation($userId = null){
        $this->filters = ["`book`.`status` = '".BOOK_STATUS_AVAILABLE."'"];
        $this->orders = ["rand()"];

        $this->loadBooks(1,BOOKS_VIEW_LIMIT_RECOMENDED);
        return $this->getResult();
    }

    public function toJSON(){
        $categories = new Categories();
        foreach ($this->books as $index => $book){

            $categoriesJSON = [$categories->getCategoryById($book["category_id"])];
            $this->books[$index]["categories"] = $categoriesJSON;
        }

        $result = ['data'=>$this->books,
                   'page'=>$this->page];
        if($this->returnStats){
            $result[0]['stats'] = ['total'=>$this->foundRows];
        }
        return json_encode($result);
    }

    private function loadBooks($page=1,$limit=BOOKS_VIEW_LIMIT_DEFAULT){
        if(isset($this->bookId)){
            $this->filters[] = "book.book_id = ".$this->bookId;
        }

        $filterQuery = "";
        if(count($this->filters)>0){
            $filterQuery = " WHERE " . implode(" AND ",$this->filters);
        }

        $orderQuery = "";
        if(count($this->orders)>0){
            $orderQuery = " ORDER BY " . implode(" , ",$this->orders);
        }


        $queries["command"] = " SELECT ";
        if($this->returnStats){
            $queries["option"] = " SQL_CALC_FOUND_ROWS ";
        }
        $queries["fields"] = " `book`.`book_id`,
                               `book`.`title`,
                               `book`.`description`,
                               `book`.`category_id`,
                               `book`.`isbn`,
                               `book`.`publisher_id`,
                               `book`.`language`,
                               `book`.`user_id`,
                               `book`.`status`,
                               `book`.`loan_period`,
                               `book`.`enter_date`,
                               `book`.`recommended`,
                               `book`.`rating`,
                                GROUP_CONCAT(`author`.`name` SEPARATOR ', ') as authors ";
        $queries["tables"] = "  FROM `booksharing`.`book`
                                LEFT JOIN `booksharing`.`book_author` ON `book`.`book_id` = `booksharing`.`book_author`.`book_id` 
                                LEFT JOIN `booksharing`.`author` ON `author`.`author_id` = `booksharing`.`book_author`.`author_id` 
                                 ";
        $queries["conditions"] = $filterQuery;
        $queries["group"] = " GROUP BY  `book`.`book_id` ";
        $queries["order"] = $orderQuery;
        $queries["limit"] = " LIMIT ".(($page-1)*$limit).",".$limit;

        $query = implode("",$queries);
        $this->books = $this->db->selectArray($query);
        if($this->returnStats){
            $this->foundRows = $this->db->selectValue("SELECT FOUND_ROWS();");
        }
    }

    public function borrowBook($userId)
    {
        if(isset($this->bookId)){
            $userObj = new User($userId);
            $user = $userObj->getUser();
            $totalBorrowed = 0;
            if(isset($user)){
                $totalBorrowed = $user["total_borrowed"];
                if($totalBorrowed>=MAX_BORROWED_BOOK){
                    throw new Exception ("Maximum book ".MAX_BORROWED_BOOK);
                }
            }else{
                throw new Exception ("User not found");
            }
            $owner = $this->getOwner($this->bookId);
            if($userId==$owner){
                throw new Exception ("User is the owner of the book");
            }

            if($this->checkStatus()==BOOK_STATUS_AVAILABLE){
                $ta = new TableAdapter($this->db,'booksharing','loan');
                $newLoan = ["book_id"=>$this->bookId,
                            "user_id"=>$userId,
                            "status"=>LOAN_STATUS_REQUESTED];
                $ta->insert($newLoan);
                $query = "UPDATE `booksharing`.`book` SET `status` = '".BOOK_STATUS_RESERVED."' WHERE `book_id` = " .$this->bookId;
                $this->db->execute($query);

                $message = "Hi, i would like to borrow your book";
                //todo: get the message from dictionary

                $notification = new Notifications();
                $notification->add($owner,$userId,NOTIFICATION_TYPE_BORROW_REQUEST,$message,$this->bookId);

            }else{
                throw new Exception ("Book not available");
            }
        }else{
            throw new Exception ("bookId required");
        }
    }

    public function rejectRequest($message){
        $this->handleRequest(LOAN_STATUS_REQUESTED,LOAN_STATUS_REJECTED,false,false,null,BOOK_STATUS_AVAILABLE,NOTIFICATION_TYPE_BORROW_REJECT,$message);
    }

    public function approveRequest(){
        $this->getBook();
        $period = $this->books[0]["loan_period"];
        //todo: get message from dictionary
        $this->handleRequest(LOAN_STATUS_REQUESTED,LOAN_STATUS_BORROWED,true,false,$period,BOOK_STATUS_BORROWED,NOTIFICATION_TYPE_BORROW_ACCEPT,"Your request has been accepted");
    }
    public function cancelRequest(){
        $this->handleRequest(LOAN_STATUS_REQUESTED,LOAN_STATUS_CANCELED,false,false,null,BOOK_STATUS_AVAILABLE,null,null);
    }

    public function returnBook(){
        //todo: get message from dictionary
        $this->handleRequest(LOAN_STATUS_BORROWED,LOAN_STATUS_RETURNED,false,true,null,BOOK_STATUS_AVAILABLE,NOTIFICATION_TYPE_BORROW_STATUS,"You have return the book");
    }


    private function handleRequest($loanStatusBefore,$loanStatusAfter,$setStartDate,$setEndDate,$period,$bookStatus,$notificationType,$notificationMessage){
        if(isset($this->bookId)){
            //update loan table
            $query = "SELECT loan_id,user_id FROM booksharing.loan WHERE book_id = " .$this->bookId. " AND
                      status =".$this->db->quote($loanStatusBefore)." ORDER BY timestamp DESC LIMIT 0,1; ";
            $loan = $this->db->select($query);
            if(count($loan)==0){
                throw new Exception ("loan data not found");
            }
            $loanId = $loan[0]->loan_id;
            $userId = $loan[0]->user_id;

            //update load data
            $periodQuery = isset($period)?", period = ".$period:"";
            $startDateQuery = $setStartDate?", start_date = NOW() ":"";
            $endDateQuery = $setEndDate?", end_date = NOW() ":"";

            $query = "UPDATE booksharing.loan SET 
                        status = ".$this->db->quote($loanStatusAfter)."  ".$periodQuery ." ".$startDateQuery ." ".$endDateQuery."
                      WHERE loan_id = " .$loanId;
            $this->db->execute($query);

            //update book status
            $query = "UPDATE booksharing.book SET status = ".$this->db->quote($bookStatus)." WHERE book_id = ".$this->bookId;
            $this->db->execute($query);

            if(isset($notificationType)){
                //add new notification
                $owner = $this->getOwner();
                $notification = new Notifications();
                $notification->add($userId,$owner,$notificationType,$notificationMessage,$this->bookId);
            }

        }else{
            throw new Exception ("bookId required");
        }
    }

    public function checkStatus(){
        if(isset($this->bookId)){
            $query = "SELECT status FROM `booksharing`.`book` WHERE `book_id` = ".$this->bookId;
            return $this->db->selectValue($query);
        }else{
            throw new Exception ("bookId required");
        }
    }

    public function  getOwner(){
        if(isset($this->bookId)){
            $query = "SELECT user_id FROM `booksharing`.`book` WHERE `book_id` = ".$this->bookId;
            return $this->db->selectValue($query);
        }else{
            throw new Exception ("bookId required");
        }
    }

    public function setTitle($title){
        if(isset($this->bookId)){
            $this->properties["title"]=$title;
        }else{
            throw new Exception ("bookId required");
        }
    }
    public function setDescription($description){
        if(isset($this->bookId)){
            $this->properties["description"]=$description;
        }else{
            throw new Exception ("bookId required");
        }
    }
    public function setLanguage($language){
        $validLanguage = [LANGUAGE_DE,LANGUAGE_EN,LANGUAGE_ID];
        if(!in_array($language,$validLanguage)){
            throw new Exception ("language invalid");
        }

        if(isset($this->bookId)){
            $this->properties["language"]=$language;
        }else{
            throw new Exception ("bookId required");
        }
    }
    public function setISBN($isbn){
        if(isset($this->bookId)){
            $this->properties["isbn"]=$isbn;
        }else{
            throw new Exception ("bookId required");
        }
    }
    public function setCategoryId($categoryId){
        if(!is_numeric($categoryId)){
            throw new Exception ("category id not numeric");
        }

        if(isset($this->bookId)){
            $this->properties["category_id"]=$categoryId;
        }else{
            throw new Exception ("bookId required");
        }
    }
    public function setLoanPeriod($loanPeriod){
        if(!is_numeric($loanPeriod)){
            throw new Exception ("loan period not numeric");
        }
        if(isset($this->bookId)){
            $this->properties["loan_period"]=$loanPeriod;
        }else{
            throw new Exception ("bookId required");
        }
    }
    public function saveProperties(){
        if(count($this->properties)==0){
            throw new Exception ("no new property found");
        }

        if(isset($this->bookId)){
            $this->properties["book_id"] = $this->bookId;
            $ta = new TableAdapter($this->db,"booksharing","book");
            $result =$ta->insert($this->properties,true);
        }else{
            throw new Exception ("bookId required");
        }

    }

    private function getResult(){
        if($this->inJSON){
            return $this->toJSON();
        }else{
            return $this->books;
        }
    }
}