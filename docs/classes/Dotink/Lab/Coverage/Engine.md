# Engine



#### Namespace

`Dotink\Lab\Coverage`

#### Imports

<table>

	<tr>
		<th>Alias</th>
		<th>Namespace / Target</th>
	</tr>
	
	<tr>
		<td>Broker</td>
		<td>TokenReflection\Broker</td>
	</tr>
	
</table>

## Properties

### Instance Properties
#### <span style="color:#6a6e3d;">$broker</span>

The reflection analysis broker

#### <span style="color:#6a6e3d;">$ignoredClasses</span>

Classes which are being ignored

#### <span style="color:#6a6e3d;">$ignoredFunctions</span>

Functions which are being ignored

#### <span style="color:#6a6e3d;">$ignoredFiles</span>

Files which are being ignored

#### <span style="color:#6a6e3d;">$ignoredMethods</span>

#### <span style="color:#6a6e3d;">$ignoredNamespaces</span>

Namespaces which are being ignored

#### <span style="color:#6a6e3d;">$preservedFiles</span>

Files which are being preserved from ignore

#### <span style="color:#6a6e3d;">$started</span>




## Methods

### Instance Methods
<hr />

#### <span style="color:#3e6a6e;">__construct()</span>


<hr />

#### <span style="color:#3e6a6e;">baseFile()</span>

Create a new coverage engine

###### Returns

<dl>
	
		<dt>
			void
		</dt>
		<dd>
			Provides no return value.
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">start()</span>

Begin code coverage checks

###### Parameters

<table>
	<thead>
		<th>Name</th>
		<th>Type(s)</th>
		<th>Description</th>
	</thead>
	<tbody>
			
		<tr>
			<td>
				$report
			</td>
			<td>
									<a href="../../../../interfaces/Dotink/Lab/Coverage/ReportInterface.md">ReportInterface</a>
				
			</td>
			<td>
				The report to add information to
			</td>
		</tr>
			
	</tbody>
</table>

###### Returns

<dl>
	
		<dt>
			void
		</dt>
		<dd>
			Provides no return value.
		</dd>
	
</dl>


<hr />

#### <span style="color:#3e6a6e;">ignoreFile()</span>


<hr />

#### <span style="color:#3e6a6e;">ignoreClass()</span>


<hr />

#### <span style="color:#3e6a6e;">ignoreFunction()</span>


<hr />

#### <span style="color:#3e6a6e;">ignoreNamespace()</span>


<hr />

#### <span style="color:#3e6a6e;">isStarted()</span>


<hr />

#### <span style="color:#3e6a6e;">preserveFile()</span>


<hr />

#### <span style="color:#3e6a6e;">process()</span>


<hr />

#### <span style="color:#3e6a6e;">stop()</span>


<hr />

#### <span style="color:#3e6a6e;">ignore()</span>


<hr />

#### <span style="color:#3e6a6e;">ignoreByPattern()</span>




