<h1>Macros</h1>

<p>
Following strings will be replaced on runtime on the templates who use it:
</p>
<br>

<p>

<li>_sitename_: Site name, as defined in setup.</li>
<li>_incident_title_: Title of the ticket.</li>
<li>_username_: Name of the user who receive the mail (login name).</li>
<li>_fullname_: Fullname of the user who receive the mail.</li>
<li>_incident_id_: ID of ticket.</li>
<li>_access_url_: Incident URL.</li>
<li>_creation_timestamp_: Date/Time of ticket creation.</li>
<li>_update_timestamp_: Last time ticket was updated.</li>
<li>_owner_: User who manages the ticket.</li>
<li>_group_: Group assigned to this ticket.</li>
<li>_author_: Creator of ticket.</li>
<li>_priority_: Ticket priority.</li>
<li>_status_: Status of the ticket.</li>
<li>_resolution_: Resolution of the ticket.</li>
<li>_incident_own_email_: Owner's email.</li>
<li>_incident_gr_email_: Group's email.</li>
<li>_incident_creator_email_: Author's email.</li>

</p>

<p>
<b>Example To:</b>
<br>
_incident_own_email_
</p>

<p>
<b>Example Subject:</b>
<br>
Incident #_incident_id_ _incident_title_ 
</p>

<p>
<b>Example Text:</b>
<br>
Ticket #_incident_id_ ((_incident_title_))
<br>
   _access_url_
<br>
===================================================
<br>
    ID          : #_incident_id_ - _incident_title_
<br>
    CREATED ON  : _creation_timestamp_
<br>
    LAST UPDATE : _update_timestamp_
<br>
    GROUP       : _group_
<br>
    AUTHOR      : _author_
<br>
    ASSIGNED TO : _owner_
<br>
    PRIORITY    : _priority_
<br>
   
===================================================
<br>

_incident_main_text_
<br>
===================================================
<br>
</p>
