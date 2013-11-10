<?php

    require_once "OneDB_MongoObject.class.php";

    /* OneDB_User implementation
     */
    class OneDB_User extends OneDB_MongoObject {

        public function __construct( &$collection, $objectID = NULL, $firstLoadDataIfObjectIDWasSet = NULL ) {

            $this->addTrigger('name', 'before', function( &$newName, $oldName, $self ) {
                if (!preg_match('/^[a-z0-9\-_\s\.]+$/i', $newName))
                    throw new Exception("Invalid user name '$newName'!");
                $newName = strtolower( $newName );
            });
            
            $this->addEventListener('create', function( &$that ) {
                /* Maybe we want to send a new email when user is created? */
            });
            
            $this->addTrigger('password', 'before', function( &$newPassword, $oldPassword, $self ) {
                $newPassword = md5( $newPassword );
            });
            
            $this->addTrigger('email', 'before', function( $newEmail, $oldEmail, $self ) {
                if (empty( $newEmail ) && empty( $oldEmail))
                    return;
                if ($newEmail != $oldEmail && !is_email( $newEmail ))
                    throw new Exception("Email address '$newEmail' seems to be invalid!");
            });
            
            $this->addTrigger('email', 'after', function( $newEmail, $oldEmail, $self ) {
                if (!empty( $oldEmail ) && ( $newEmail != $oldEmail )) {
                    /* Maybe we want to notify the user of it's email address change ? */
                }
            });
            
            $this->addTrigger('phone', 'before', function( $newPhone, $oldPhone, $self) {
                if (!empty($phone) && !preg_match('/^[\d]+$/', $newPhone))
                    throw new Exception("Invalid phone number: '$newPhone', please use only digits");
            });
            
            $this->addTrigger('mobile', 'before', function( $newMobile, $oldMobile, $self) {
                if (!empty($newMobile) && !preg_match('/^[\d]+$/', $newMobile))
                    throw new Exception("Invalid mobile number: '$newMobile', please use only digits");
            });
            
            $this->addTrigger('firstName', 'before', function( $newName, $oldName, $self) {
                if (!empty($firstName) && !preg_match('/^[a-z][a-z\s\.\-]+$/i', $newName))
                    throw new Exception("Invalid name, please use only letters and spaces");
            });

            $this->addTrigger('lastName', 'before', function( $newName, $oldName, $self) {
                if (!empty($firstName) && !preg_match('/^[a-z][a-z\s\.\-]+$/i', $newName))
                    throw new Exception("Invalid name, please use only letters and spaces");
            });
            
            parent::__construct( $collection, $objectID, $firstLoadDataIfObjectIDWasSet );

        }
        
        /* Is the user member of the group <str> groupName?
         */
        
        public function memberOf( $groupName ) {
            return $groupName === NULL ||
                   strtolower( $groupName ) == 'everyone' ||
                   in_array( $groupName, $this->groups ) ? 
            TRUE : FALSE;
        }
        
    }

?>