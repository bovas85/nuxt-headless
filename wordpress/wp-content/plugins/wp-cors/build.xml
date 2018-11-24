<project name="wp-cors">

  <property name="dist" value="${basedir}/.."/>
  <property name="m2_repo" value="${user.home}/.m2/repository"/>
  <property name="project.name" value="wp-cors"/>
  <property name="src" value="${basedir}"/>
  <property name="version" value="0.2.1"/>
  <property name="zipName" value="${project.name}.zip"/>

  <target name="clean">
    <delete file="${dist}/${zipName}"/>
  </target>

  <target name="compile">
  </target>

  <target name="package" depends="compile">
    <zip destfile="${dist}/${zipName}"
         basedir="${src}"
         excludes=".svn/**/*, jasmine*, **/*~, **/*.log, **/*.swp, **/*.xcf"
         update="true"
    />
  </target>

</project>
