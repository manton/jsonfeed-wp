#!/bin/bash
#
# Commits and pushes to the wordpress.org plugin directory repo.
# Inspired by and derived from https://github.com/GaryJones/wordpress-plugin-svn-deploy and https://github.com/miya0001/travis2wpplugin/

PLUGINSLUG='jsonfeed'
SVNPATH="/tmp/$PLUGINSLUG"
SVNURL="https://plugins.svn.wordpress.org/$PLUGINSLUG"
MAINFILE="$PLUGINSLUG.php"
default_svnuser=""

echo "Checking version in main plugin file matches version in readme.txt file..."
echo

# Check version in readme.txt is the same as plugin file after translating both to Unix line breaks to work around grep's failure to identify Mac line breaks
PLUGINVERSION=$(grep -i "Version:" $MAINFILE | awk -F' ' '{print $NF}' | tr -d '\r')
echo "$MAINFILE version: $PLUGINVERSION"
READMEVERSION=$(grep -i "Stable tag:" readme.txt | awk -F' ' '{print $NF}' | tr -d '\r')
echo "readme.txt version: $READMEVERSION"
if [ "$READMEVERSION" = "trunk" ]; then
	echo "Version in readme.txt & $MAINFILE don't match, but Stable tag is trunk. Let's continue..."
elif [ "$PLUGINVERSION" != "$READMEVERSION" ]; then
	echo "Version in readme.txt & $MAINFILE don't match. Exiting...."
	exit 1;
elif [ "$PLUGINVERSION" = "$READMEVERSION" ]; then
	echo "Versions match in readme.txt and $MAINFILE. Let's continue..."
fi

# Check for git tag (may need to allow for leading "v"?)
# if git show-ref --tags --quiet --verify -- "refs/tags/$PLUGINVERSION"
if git show-ref --tags --quiet --verify -- "refs/tags/$PLUGINVERSION"
	then
		echo "Git tag $PLUGINVERSION does exist. Let's continue..."
	else
		echo "Tagging this Release in Git"
		git tag -a $PLUGINVERSION --cleanup=verbatim 
		git push --tags
fi
#

printf "Your WordPress repo SVN username ($default_svnuser): "
read -e input
SVNUSER="${input:-$default_svnuser}" # Populate with default if empty
echo

echo "Your SVN password"
read -e SVNPASSWORD
if  [ -z "$SVNPASSWORD" ]; then
  echo "Password cannot be empty"
  exit 1
fi



echo "Creating local copy of SVN repo trunk..."
svn checkout $SVNURL $SVNPATH --depth immediates
svn update --quiet $SVNPATH/trunk --set-depth infinity

echo "Exporting the HEAD of master from git to the trunk of SVN"
git checkout-index -a -f --prefix=$SVNPATH/trunk/


# If submodule exist, recursively check out their indexes
if [ -f ".gitmodules" ]
	then
		echo "Exporting the HEAD of each submodule from git to the trunk of SVN"
		git submodule init
		git submodule update
		git config -f .gitmodules --get-regexp '^submodule\..*\.path$' |
			while read path_key path
			do
				#url_key=$(echo $path_key | sed 's/\.path/.url/')
				#url=$(git config -f .gitmodules --get "$url_key")
				#git submodule add $url $path
				echo "This is the submodule path: $path"
				echo "The following line is the command to checkout the submodule."
				echo "git submodule foreach --recursive 'git checkout-index -a -f --prefix=$SVNPATH/trunk/$path/'"
				git submodule foreach --recursive 'git checkout-index -a -f --prefix=$SVNPATH/trunk/$path/'
			done
fi

if [ -e "assets" ]; then
	# Support for the /assets folder on the .org repo.
	echo "Moving assets."
	# Make the directory if it doesn't already exist
	mkdir -p $SVNPATH/assets/
	mv $SVNPATH/trunk/assets/* $SVNPATH/assets/
	svn add --force $SVNPATH/assets/
	svn delete --force $SVNPATH/trunk/assets
fi

# Always ignore these files regardless of what svnignore says 
svn propset svn:ignore "README.md
Thumbs.db
.github/*
.git
.gitattributes
.gitignore" "$SVNPATH/trunk/"

if [ -e ".svnignore" ]; then
		echo "Using Ignore Instructions from .svnignore"
		svn propset -q -R svn:ignore -F .svnignore "$SVNPATH/trunk/"
fi

echo "Changing directory to SVN and committing"
cd $SVNPATH/trunk

echo "Run svn add"
svn st | grep '^!' | sed -e 's/\![ ]*/svn del -q /g' | sh
echo "Run svn del"
svn st | grep '^?' | sed -e 's/\?[ ]*/svn add -q /g' | sh

echo "Commit to $SVNPATH."
svn commit -m "commit version $PLUGINVERSION" --username $SVNUSER --password $SVNPASSWORD

echo "Creating new SVN tag and committing it."
cd $SVNPATH
svn copy --quiet trunk/ tags/$PLUGINVERSION
svn delete --force --quiet $SVNPATH/tags/$PLUGINVERSION/assets
svn delete --force --quiet $SVNPATH/tags/$PLUGINVERSION/trunk
svn update --quiet --accept working $SVNPATH/tags/$PLUGINVERSION
cd $SVNPATH/tags/$PLUGINVERSION
svn commit --username=$SVNUSER --password $SVNPASSWORD -m "Tagging version $PLUGINVERSION"


echo
svn st
echo "Removing temporary directory $SVNPATH."
cd $SVNPATH
cd ..
rm -fr $SVNPATH/

echo "*** FIN ***"
