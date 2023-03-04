# Schema

In addition to the official `annotation/attribute` support, cycle bundle provides the ability to describe schema using `xml`

## Xml

Create dir `config/cycle` and create a mapping file `User.orm.xml`

```xml
<?xml version="1.0" encoding="utf-8"?>
<cycle-mapping xmlns="http://cycle.dev/schema/mapping">
  <entity name="App\Entity\User" table="users">
    <behaviors>
      <created-at field="createdAt"/>
      <updated-at field="updatedAt" nullable="true"/>
    </behaviors>
    <id name="id" column="id" type="integer">
      <generator strategy="AUTO" />
    </id>
    <field name="username" column="username" type="string" length="100" nullable="false" unique="true" unique-key-name="uk_username" comment="用户名"/>
    <field name="salt" column="salt" type="string" nullable="true" />
    <field name="password" column="password" type="string" nullable="true" />
    <field name="email" column="email" type="string" nullable="true" />
    <field name="gender" type="smallint" column="gender" length="1" precision="0" scale="0" nullable="false"/>
    <field name="createdAt" column="created_at" type="datetime"/>
    <field name="updatedAt" column="updated_at" type="datetime" nullable="true"/>
    <has-many field="oauthAccounts" target="App\Entity\UserOAuth"/>
    <has-one field="profile" target="App\Entity\UserProfile"/>
  </entity>
</cycle-mapping>

```

like the `doctrine`, but it is a little different.

## Annotation/attribute

This is no different from the official. please reference [https://cycle-orm.dev/docs/annotated-entity](https://cycle-orm.dev/docs/annotated-entity)
