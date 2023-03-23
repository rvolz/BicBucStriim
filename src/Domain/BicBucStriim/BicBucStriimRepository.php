<?php

namespace App\Domain\BicBucStriim;

use App\Domain\User\User;
use Exception;
use PDO;
use RedBeanPHP\OODBBean;

interface BicBucStriimRepository
{
    # Name to the bbs db
    public const DBNAME = 'data.db';
    # Thumbnail dimension (they are square)
    public const THUMB_RES = 160;

    /**
    * Create an empty BBS DB, just with the initial admin user account, so that login is possible.
    * @param string $dataPath Path to BBS DB
    */
    public function createDataDb($dataPath = 'data/data.db');

    /**
     * Is our own DB open?
     * @return boolean	true if open, else false
     */
    public function dbOk(): bool;

    /**
     * Return the PDO object for the internal DB
     * @return PDO|null
     */
    public function getDb(): ?PDO;

    /**
     * Intialize the global settings array
     * @return array
     */
    public function initSettings(): array;

    /**
     * Find all configuration values in the settings DB
     * @return array configuration values
     */
    public function configs(): array;

    /**
     * Find a specific configuration value by name
     * @param string $name configuration parameter name
     * @return mixed  config paramter or null
     */
    public function config(string $name);

    /**
     * Save all configuration values in the settings DB
     * @param array $configs array of configuration values
     */
    public function saveConfigs(array $configs);

    /**
     * Find all user records in the settings DB
     * @return array user data
     */
    public function users(): array;

    /**
     * Find a specific user in the settings DB
     * @param string $userid
     * @return ?object data or NULL if not found
     */
    public function user(string $userid): ?object;

    /**
     * Find a user by user name in the settings DB
     * @param string $username user name
     * @return ?object user data or NULL if not found
     */
    public function userByName(string $username): ?object;

    /**
     * Add a new user account.
     * The username must be unique. Name and password must not be empty.
     * @param string $username login name for the account, must be unique
     * @param string $password  clear text password
     * @return ?object user account or null if the user exists or one of the parameters is empty
     * @throws Exception if the DB operation failed
     */
    public function addUser(string $username, string $password): ?object;

    /**
     * Delete a user account from the database.
     * The admin account (ID 1) can't be deleted.
     * @param int $userid
     * @return bool true if a user was deleted else false
     */
    public function deleteUser(int $userid): bool;

    /**
     * Update an existing user account.
     * The username cannot be changed and the password must not be empty.
     * @param int $userid integer
     * @param string $password new clear text password or old encrypted password
     * @param string $languages comma-delimited set of language identifiers
     * @param string $tags string comma-delimited set of tags
     * @param string $role "1" for admin "0" for normal user
     * @return object   updated user account or null if there was an error
     */
    public function changeUser(int $userid, string $password, string $languages, string $tags, string $role): ?object;

    /**
     * Find all ID templates in the settings DB
     * @return array id templates
     */
    public function idTemplates(): array;

    /**
     * Find a specific ID template in the settings DB
     * @param string $name template name
     * @return ?object               IdTemplate or null
     */
    public function idTemplate(string $name): ?object;

    /**
     * Add a new ID template
     * @param string $name unique template name
     * @param string $value URL template
     * @param string $label display label
     * @return object template record or null if there was an error
     */
    public function addIdTemplate(string $name, string $value, string $label): object;

    /**
     * Delete an ID template from the database
     * @param string $name template namne
     * @return bool true if template was deleted else false
     */
    public function deleteIdTemplate(string $name): bool;

    /**
     * Update an existing ID template. The name cannot be changed.
     * @param string $name        template name
     * @param string $value        URL template
     * @param string $label        display label
     * @return ?object updated template or null if there was an error
     */
    public function changeIdTemplate($name, $value, $label);

    /**
     * Find a Calibre item.
     * @param int    $calibreType
     * @param int    $calibreId
     * @return ?object
     */
    public function getCalibreThing($calibreType, $calibreId): ?object;

    /**
     * Add a new reference to a Calibre item.
     *
     * Calibre items are identified by type, ID and name. ID and name
     * are used to find items that can be renamed, like authors.
     *
     * @param int       $calibreType
     * @param int       $calibreId
     * @param string    $calibreName
     * @return            object, the Calibre item
     */
    public function addCalibreThing($calibreType, $calibreId, $calibreName);

    /**
     * Delete an author's thumbnail image.
     *
     * Deletes the thumbnail artefact, and then the CalibreThing if that
     * has no further references.
     *
     * @param int    $authorId    Calibre ID of the author
     * @return   bool     true if deleted, else false
     */
    public function deleteAuthorThumbnail($authorId);

    /**
     * Return the author thumbnail file related to this Calibre entitiy.
     * @return ?object    Path to thumbnail file or null
     */
    public function getFirstArtefact($calibreThing);

    /**
     * Get the file name of an author's thumbnail image.
     * @param int    $authorId    Calibre ID of the author
     * @return        ?object file name of the thumbnail image, or null
     */
    public function getAuthorThumbnail($authorId);

    /**
     * Change the thumbnail image for an author.
     *
     * @param int       $authorId    Calibre ID of the author
     * @param string    $authorName    Calibre name of the author
     * @param boolean   $clipped    true = image should be clipped, else stuffed
     * @param string    $file        File name of the input image
     * @param string    $mime        Mime type of the image
     * @return            bool file name of the thumbnail image, or null
     */
    public function editAuthorThumbnail($authorId, $authorName, $clipped, $file, $mime);

    /**
     * Get the thumbnail for a book was already generated.
     * @param int    $id    Calibre book ID
     * @return string      The path to the file
     */
    public function getExistingTitleThumbnail(int $id): string;

    /**
     * Checks if the thumbnail for a book was already generated.
     * @param int    $id    Calibre book ID
     * @return bool        true if the thumbnail file exists, else false
     */
    public function isTitleThumbnailAvailable(int $id): bool;

    /**
     * Returns the path to a thumbnail of a book's cover image.
     *
     * If a thumbnail doesn't exist the function tries to make one from the cover.
     * The thumbnail dimension generated is 160*160, which is more than what
     *
     * The function expects the input file to be a JPEG.
     *
     * @param int     $id       book id
     * @param string  $cover    path to cover image
     * @param bool    $clipped  true = clip the thumbnail, else stuff it
     * @return string, thumbnail path
     * @throws NoThumbnailException if the thumbnail could not be created
     */
    public function titleThumbnail($id, $cover, $clipped): string;

    /**
     * Delete existing thumbnail files
     * @return bool false if there was an error
     */
    public function clearThumbnails();

    /**
     * Return author links releated to this Calibre entitiy.
     * @param OODBBean $calibreThing
     * @return array    all available author links
     */
    public function getLinks($calibreThing);

    /**
     * Return all links defined for an author.
     * @param int $authorId Calibre ID for the author
     * @return array    author links
     */
    public function authorLinks($authorId);

    /**
     * Add a link for an author.
     * @param int       $authorId    Calibre ID for author
     * @param string    $authorName    Calibre name for author
     * @param string    $label        link label
     * @param string    $url        link url
     * @return object    created author link
     */
    public function addAuthorLink($authorId, $authorName, $label, $url);

    /**
     * Delete a link from the collection defined for an author.
     * @param int    $authorId    Calibre ID for author
     * @param int    $linkId        ID of the author link
     * @return boolean            true if the link was deleted, else false
     */
    public function deleteAuthorLink($authorId, $linkId);

    /**
     * Return the author note text related to this Calibre entitiy.
     * @param OODBBean $calibreThing
     * @return ?object    text or null
     */
    public function getFirstNote($calibreThing);

    /**
     * Get the note text fro an author.
     * @param int    $authorId    Calibre ID of the author
     * @return ?object        note text or null
     */
    public function authorNote($authorId);

    /**
     * Set the note text for an author.
     * @param int       $authorId    Calibre ID for author
     * @param string    $authorName  Calibre name for author
     * @param string    $mime        mime type for the note's content
     * @param string    $noteText    note content
     * @return object   created/edited note
     */
    public function editAuthorNote($authorId, $authorName, $mime, $noteText);

    /**
     * Delete the note for an author
     * @param int    $authorId    Calibre ID for author
     * @return boolean            true if the note was deleted, else false
     */
    public function deleteAuthorNote($authorId);
}
